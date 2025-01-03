<?php

namespace Paki\AutoTest\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateTests extends Command
{
    protected $signature = 'generate:tests 
                            {--directory= : Le répertoire des classes à explorer (par défaut Services)}
                            {--response= : La réponse attendue pour les tests générés}
                            {--assertion= : Type d\'assertion pour les tests (par défaut assertNotNull)}
                            {--mock= : Dépendances à mocker pour les tests générés}
                            {--performance= : Activer les tests de performance (true/false)}';
                            
    protected $description = 'Génère automatiquement des tests pour les méthodes annotées avec @Testable';

    public function handle()
    {
        $directory = $this->option('directory') ?: 'Services'; // Répertoire par défaut
        $files = glob(app_path("$directory/*.php"));
        $expectedResponse = $this->option('response');
        $assertionType = $this->option('assertion') ?: 'assertNotNull';
        $mockDependencies = $this->option('mock');
        $performanceTest = $this->option('performance');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);
            $reflector = new \ReflectionClass($className);

            foreach ($reflector->getMethods() as $method) {
                if ($this->hasTestableAnnotation($method)) {
                    $this->generateTest($reflector->getName(), $method->getName(), $expectedResponse, $assertionType, $mockDependencies, $performanceTest, $method);
                }
            }
        }

        $this->info('Tests générés avec succès!');
    }

    protected function getClassNameFromFile($file)
    {
        $content = file_get_contents($file);
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch);
        preg_match('/class\s+(\w+)/', $content, $classMatch);

        $namespace = $namespaceMatch[1] ?? null;
        $className = $classMatch[1] ?? null;

        return $namespace && $className ? "$namespace\\$className" : null;
    }

    protected function hasTestableAnnotation(\ReflectionMethod $method)
    {
        $docComment = $method->getDocComment();
        return $docComment && Str::contains($docComment, '@Testable');
    }

    protected function generateTest($className, $methodName, $expectedResponse, $assertionType, $mockDependencies, $performanceTest, $method)
    {
        $testClassName = Str::afterLast($className, '\\') . 'Test';
        $testFilePath = base_path("tests/Unit/{$testClassName}.php");

        if (!file_exists($testFilePath)) {
            file_put_contents($testFilePath, $this->getTestClassTemplate($testClassName));
        }

        $testMethodTemplate = $this->getTestMethodTemplate($className, $methodName, $expectedResponse, $assertionType, $mockDependencies, $performanceTest, $method);

        // Ajouter le test uniquement s'il n'existe pas déjà
        $currentContent = file_get_contents($testFilePath);
        if (!Str::contains($currentContent, "function test{$methodName}")) {
            $updatedContent = str_replace('// Methods', "\n{$testMethodTemplate}\n// Methods", $currentContent);
            file_put_contents($testFilePath, $updatedContent);
        }
    }

    protected function getTestClassTemplate($testClassName)
    {
        return <<<PHP
<?php

namespace Tests\Unit;

use Tests\TestCase;

class {$testClassName} extends TestCase
{
    // Methods
}
PHP;
    }

    protected function getTestMethodTemplate($className, $methodName, $expectedResponse, $assertionType, $mockDependencies, $performanceTest, $method)
    {
        $responseCheck = $expectedResponse ? "\n        \$this->assertEquals('$expectedResponse', \$result);" : "\n        \$this->$assertionType(\$result);";
        $mockSetup = $mockDependencies ? "\n        \$mock = \Mockery::mock('$mockDependencies');" : '';
        $performanceCheck = $performanceTest ? "\n        \$start = microtime(true);" : '';
        $performanceAssert = $performanceTest ? "\n        \$this->assertTrue(microtime(true) - \$start < 2); // Test de performance" : '';

        // Vérifier si des annotations spécifiques sont présentes
        $annotationResponse = $this->getAnnotationValue($method, '@TestResponse');
        $annotationAssertion = $this->getAnnotationValue($method, '@assertion');
        
        // Si des annotations @TestResponse ou @assertion existent, on les applique
        if ($annotationResponse) {
            $responseCheck = "\n        \$this->assertEquals('$annotationResponse', \$result);";
        }
        
        if ($annotationAssertion) {
            $assertionType = $annotationAssertion;
        }

        return <<<PHP
    public function test{$methodName}()
    {
        \$instance = new \{$className}();
        $mockSetup
        \$result = \$instance->{$methodName}();
        $performanceCheck
        $responseCheck
        $performanceAssert
    }
PHP;
    }

    protected function getAnnotationValue(\ReflectionMethod $method, $annotation)
    {
        $docComment = $method->getDocComment();
        preg_match('/' . preg_quote($annotation) . '\s+"(.*?)"/', $docComment, $matches);
        return $matches[1] ?? null;
    }
}

<?php

namespace Paki\TestGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateTests extends Command
{
    protected $signature = 'generate:tests {--response= : La réponse attendue pour les tests générés}';
    protected $description = 'Génère automatiquement des tests pour les méthodes annotées avec @Testable';

    public function handle()
    {
        $directory = app_path('Services'); // Répertoire où vos classes sont situées
        $files = glob($directory . '/*.php');
        $expectedResponse = $this->option('response');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);
            $reflector = new \ReflectionClass($className);

            foreach ($reflector->getMethods() as $method) {
                if ($this->hasTestableAnnotation($method)) {
                    $this->generateTest($reflector->getName(), $method->getName(), $expectedResponse);
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

    protected function generateTest($className, $methodName, $expectedResponse = null)
    {
        $testClassName = Str::afterLast($className, '\\') . 'Test';
        $testFilePath = base_path("tests/Unit/{$testClassName}.php");

        if (!file_exists($testFilePath)) {
            file_put_contents($testFilePath, $this->getTestClassTemplate($testClassName));
        }

        $testMethodTemplate = $this->getTestMethodTemplate($className, $methodName, $expectedResponse);

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

    protected function getTestMethodTemplate($className, $methodName, $expectedResponse = null)
    {
        $responseCheck = $expectedResponse ? "\n        \$this->assertEquals('$expectedResponse', \$result);" : "\n        \$this->assertNotNull(\$result);";

        return <<<PHP
    public function test{$methodName}()
    {
        \$instance = new \{$className}();
        // Remplacer les valeurs ci-dessous par des entrées valides
        \$result = \$instance->{$methodName}();
{$responseCheck}
    }
PHP;
    }
}

<?php

namespace Paki\TestGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use File;
use ReflectionMethod;

class GenerateTests extends Command
{
    protected $signature = 'generate:tests {--directory=Services : Le répertoire des classes à explorer (ex : Services, Controllers)}';
    protected $description = 'Génère automatiquement des tests pour les méthodes annotées avec @Testable';

    public function handle()
    {
        $directory = app_path($this->option('directory')); // Répertoire configurable via option

        // Vérifiez si le répertoire existe avant de continuer
        if (!File::exists($directory)) {
            $this->error("Le répertoire spécifié $directory n'existe pas.");
            return;
        }

        $files = glob($directory . '/*.php');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);

            // Si le nom de la classe n'est pas trouvé, passez à la suivante
            if (!$className) {
                continue;
            }

            $reflector = new \ReflectionClass($className);

            foreach ($reflector->getMethods() as $method) {
                if ($this->hasTestableAnnotation($method)) {
                    // Récupérer les annotations @response et @assertion
                    $annotations = $this->getAnnotations($method);
                    $expectedResponse = $annotations['TestResponse'] ?? null;
                    $assertionType = $annotations['assertion'] ?? 'assertNotNull';

                    $this->generateTest($reflector->getName(), $method->getName(), $expectedResponse, $assertionType);
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

    protected function hasTestableAnnotation(ReflectionMethod $method)
    {
        $docComment = $method->getDocComment();
        return $docComment && Str::contains($docComment, '@Testable');
    }

    protected function getAnnotations(ReflectionMethod $method)
    {
        $docComment = $method->getDocComment();
        preg_match_all('/@(\w+)\s*=\s*([^\s]+)/', $docComment, $matches, PREG_SET_ORDER);

        $annotations = [];
        foreach ($matches as $match) {
            $annotations[$match[1]] = $match[2];
        }

        return $annotations;
    }

    protected function generateTest($className, $methodName, $expectedResponse = null, $assertionType = 'assertNotNull')
    {
        // Créer un nom de classe de test basé sur le nom de la classe cible
        $testClassName = Str::afterLast($className, '\\') . 'Test';
        $testFilePath = base_path("tests/Unit/{$testClassName}.php");

        // Vérifier si le fichier de test existe, sinon, créez-le
        if (!File::exists($testFilePath)) {
            File::put($testFilePath, $this->getTestClassTemplate($testClassName));
        }

        $testMethodTemplate = $this->getTestMethodTemplate($className, $methodName, $expectedResponse, $assertionType);

        // Ajouter le test uniquement s'il n'existe pas déjà
        $currentContent = File::get($testFilePath);
        if (!Str::contains($currentContent, "function test{$methodName}")) {
            // Ajouter le test au bon endroit
            $updatedContent = str_replace('// Methods', "\n{$testMethodTemplate}\n// Methods", $currentContent);
            File::put($testFilePath, $updatedContent);
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

    protected function getTestMethodTemplate($className, $methodName, $expectedResponse = null, $assertionType = 'assertNotNull')
    {
        // Sélectionner le type d'assertion en fonction de l'option
        $responseCheck = $this->getAssertionTemplate($assertionType, $expectedResponse);

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

    protected function getAssertionTemplate($assertionType, $expectedResponse)
    {
        switch ($assertionType) {
            case 'assertEquals':
                return $expectedResponse 
                    ? "\n        \$this->assertEquals('$expectedResponse', \$result);" 
                    : "\n        \$this->assertEquals('valeur_attendue', \$result);";
            case 'assertNotNull':
                return "\n        \$this->assertNotNull(\$result);";
            case 'assertNull':
                return "\n        \$this->assertNull(\$result);";
            default:
                return "\n        \$this->assertNotNull(\$result);";
        }
    }
}

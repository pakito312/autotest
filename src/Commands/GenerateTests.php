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
        $directory = $this->option('directory') ?: 'Http/Controllers'; // Répertoire par défaut
        $directoryPath = app_path($directory);
    
        if (!$this->validateDirectory($directoryPath)) {
            return;
        }
    
        $files = $this->getPhpFilesFromDirectory($directoryPath);
    
        if (empty($files)) {
            $this->info("Aucun fichier PHP trouvé dans le répertoire '$directory'.");
            return;
        }
    
        $options = $this->getTestOptions();
        $testCount = $this->processFiles($files, $options);
    
        $this->info('Tests générés avec succès!');
        $this->info("$testCount tests générés à partir du répertoire '$directory'.");
    }
    
    /**
     * Valide si le répertoire existe.
     */
    private function validateDirectory(string $directoryPath): bool
    {
        if (!is_dir($directoryPath)) {
            $this->error("Le répertoire spécifié '$directoryPath' n'existe pas.");
            return false;
        }
        return true;
    }
    
    /**
     * Récupère les fichiers PHP d'un répertoire donné.
     */
    private function getPhpFilesFromDirectory(string $directoryPath): array
    {
        return glob("$directoryPath/*.php") ?: [];
    }
    
    /**
     * Récupère les options pour la génération des tests.
     */
    private function getTestOptions(): array
    {
        return [
            'expectedResponse' => $this->option('response'),
            'assertionType' => $this->option('assertion') ?: 'assertNotNull',
            'mockDependencies' => $this->option('mock'),
            'performanceTest' => $this->option('performance'),
        ];
    }
    
    /**
     * Traite les fichiers PHP pour générer des tests.
     */
    private function processFiles(array $files, array $options): int
    {
        $testCount = 0;
    
        foreach ($files as $file) {
            try {
                $className = $this->getClassNameFromFile($file);
    
                if (!$this->validateClassName($className, $file)) {
                    continue;
                }
    
                $reflector = new \ReflectionClass($className);
    
                foreach ($reflector->getMethods() as $method) {
                    if ($this->hasTestableAnnotation($method)) {
                        $this->generateTest(
                            $reflector->getName(),
                            $method->getName(),
                            $options['expectedResponse'],
                            $options['assertionType'],
                            $options['mockDependencies'],
                            $options['performanceTest'],
                            $method
                        );
                        $testCount++;
                    }
                }
            } catch (\ReflectionException $e) {
                $this->error("Erreur lors de l'analyse de la classe dans le fichier '$file': " . $e->getMessage());
            } catch (\Exception $e) {
                $this->error("Erreur inattendue lors du traitement du fichier '$file': " . $e->getMessage());
            }
        }
    
        return $testCount;
    }
    
    /**
     * Valide si une classe existe et est valide.
     */
    private function validateClassName(?string $className, string $file): bool
    {
        if (!$className || !class_exists($className)) {
            $this->warn("La classe '$className' n'existe pas ou est invalide. Vérifiez le fichier '$file'.");
            return false;
        }
        return true;
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

    protected function getTestMethodTemplate($className, $methodName, $expectedResponse, $assertionType, $mockDependencies, $performanceTest, \ReflectionMethod $method)
{
    // Gestion des annotations
    $annotations = $this->parseAnnotations($method->getDocComment());
    $isTestable = isset($annotations['@Testable']);

    if (!$isTestable) {
        return "// Méthode {$methodName} marquée comme non testable.";
    }

    $responseCheck = isset($annotations['@TestResponse']) 
        ? "\n        \$this->assertEquals('{$annotations['@TestResponse']}', \$result);" 
        : '';

    $mockSetup = $mockDependencies 
        ? "\n        \$mock = \Mockery::mock('$mockDependencies');" 
        : '';

    $performanceCheck = $performanceTest 
        ? "\n        \$start = microtime(true);" 
        : '';
    $performanceAssert = $performanceTest 
        ? "\n        \$this->assertTrue(microtime(true) - \$start < 2, 'Performance test failed: Method took too long.');" 
        : '';

    $inputs = isset($annotations['@TestInput']) 
        ? json_decode($annotations['@TestInput'], true) 
        : [];
    $params = isset($annotations['@TestParam']) 
        ? $this->parseParams($annotations['@TestParam']) 
        : [];
        $assertStatus = isset($annotations['@AssertStatus']) 
        ? (int) $annotations['@AssertStatus'] 
        : null;
    $statusCheck = $assertStatus !== null 
        ? "\n        \$this->assertEquals($assertStatus, \$result->getStatusCode(), 'Expected HTTP status $assertStatus.');"
        : '';
        if(empty($responseCheck) && empty($statusCheck)){
            $responseCheck="\n        \$this->{$assertionType}(\$result);";
        }

    // Génération des paramètres et des inputs
    $paramsSetup = $this->generateParameterSetup($method, $params);
    $paramsAsCode = implode(', ', array_keys($params));
    $paramsAsCode=empty($paramsAsCode)?'':"$$paramsAsCode";
    $request="request";
    $inputSetup = $inputs 
        ? "\n \$request = new \\Illuminate\\Http\\Request(); \n  \$request->merge(" . var_export($inputs, true) . ");  " 
        : '';
        if(!empty($inputSetup)){
            $paramsAsCode=empty($paramsAsCode)?"$$request":"$$request,$paramsAsCode";
        }

    // Génération du code de test
    return <<<PHP
    public function test{$methodName}()
    {
        \$instance = new \\{$className}();
        $mockSetup
        $performanceCheck
        $paramsSetup
                $inputSetup
        \$result = \$instance->{$methodName}($paramsAsCode);
        $responseCheck
        $statusCheck
        $performanceAssert
    }
PHP;
}

/**
 * Analyse les annotations d'une méthode.
 */
protected function parseAnnotations($docComment)
{
    $annotations = [];
    $lines = explode("\n", $docComment);

    foreach ($lines as $line) {
        if (preg_match('/@(\w+):?\s*(.*)/', trim($line), $matches)) {
            $key = '@' . $matches[1];
            $value = trim($matches[2], '"');
            $annotations[$key] = $value;
        }
    }

    return $annotations;
}

/**
 * Génère les paramètres à partir de leurs valeurs d'annotation.
 */
protected function parseParams($paramAnnotation)
{
    $params = [];
    $pairs = explode(',', $paramAnnotation);

    foreach ($pairs as $pair) {
        list($key, $value) = array_map('trim', explode('=', $pair));
        $params[$key] = $value;
    }

    return $params;
}

/**
 * Génère l'initialisation des paramètres pour la méthode.
 */
protected function generateParameterSetup(\ReflectionMethod $method, $providedParams)
{
    $setupCode = '';
    foreach ($method->getParameters() as $param) {
        $paramName = $param->getName();
        $defaultValue = $param->isDefaultValueAvailable() 
            ? var_export($param->getDefaultValue(), true) 
            : 'null';
            if($paramName!='request'){
                $value = $providedParams[$paramName] ?? $defaultValue;
                $setupCode .= "\n        \$$paramName = $value;";
            }
    }
    return $setupCode;
}




    protected function getAnnotationValue(\ReflectionMethod $method, $annotation)
    {
        $docComment = $method->getDocComment();
        preg_match('/' . preg_quote($annotation) . '\s+"(.*?)"/', $docComment, $matches);
        return $matches[1] ?? null;
    }
}

D'accord ! Voici la version corrigée de votre `README.md`, où j'ai intégré les exemples et les commandes de manière cohérente et complète, tout en suivant la structure de votre README d'origine :

```markdown
# Laravel Test Generator

Un package Laravel pour générer automatiquement des tests unitaires pour les méthodes annotées avec `@Testable`.

## Fonctionnalités

- **Génération automatique de tests unitaires** : Génère des tests pour les méthodes annotées avec `@Testable`.
- **Personnalisation des tests via des annotations** : Définissez la réponse attendue avec `@TestResponse` et le type d'assertion avec `@assertion` directement dans le docblock des méthodes.
- **Répertoires personnalisés** : Configurez le répertoire des classes à explorer (par défaut `Services`).
- **Types d'assertions supportés** : Vous pouvez choisir parmi plusieurs types d'assertions comme `assertEquals`, `assertNotNull`, `assertNull`, etc.

## Installation

1. Ajouter le package à votre projet :
   ```bash
   composer require paki/laravel-auto-test
   ```

2. (Optionnel) Publier la configuration :
   ```bash
   php artisan vendor:publish --provider="Paki\TestGenerator\TestGeneratorServiceProvider"
   ```

## Exemple d'usage

### 1. Usage de base

Ajoutez l'annotation `@Testable` à la méthode que vous souhaitez tester. Cela indiquera au package que cette méthode doit être testée automatiquement.

```php
/**
 * @Testable
 */
public function someMethod()
{
   // Logique de la méthode
}
```

### 2. Personnaliser la réponse attendue

Si vous souhaitez définir une réponse attendue pour le test, vous pouvez utiliser l'annotation `@TestResponse` dans le docblock de votre méthode.

```php
/**
 * @Testable
 * @TestResponse "Success"
 */
public function someMethod()
{
   // Logique de la méthode
}
```

### 3. Personnaliser le type d'assertion

L'annotation `@assertion` permet de spécifier le type d'assertion à utiliser dans le test généré. Par exemple, vous pouvez utiliser `assertNull` ou `assertEquals`.

```php
/**
 * @Testable
 * @assertion assertNull
 */
public function someOtherMethod()
{
   // Logique de la méthode
}
```

### 4. Exemple complet avec plusieurs annotations

Voici un exemple où nous combinons `@Testable`, `@TestResponse` et `@assertion` pour personnaliser entièrement le test généré.

```php
/**
 * @Testable
 * @TestResponse "200 OK"
 * @assertion assertEquals
 */
public function getUserDetails()
{
   // Logique de la méthode
}
```
Ce test vérifiera que la méthode `getUserDetails()` retourne `"200 OK"` en utilisant l'assertion `assertEquals`.


```php
  /**
     * @Testable
     * @TestInput: {"name": "paki","message":"test message"}
     * @AssertStatus 201
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'name' => "required",
            'message' => "required"
        ]);
        return response()->json([], 201);
    }
```
```php
  /**
     * @Testable
     * @TestResponse "User Created"
     * @TestParam: name='test'
     */
    public function createUser($name)
    {
        return "User Created";
    }
```



### 5. Commande artisan pour générer les tests

1. **Générer des tests pour un répertoire spécifique avec une réponse et assertion personnalisées** :

   Pour générer des tests pour le répertoire `Services` avec une réponse `"Success"` et une assertion `assertEquals`, vous pouvez exécuter la commande suivante :

   ```bash
   php artisan generate:tests --directory=Services --assertion=assertEquals --response="Success"
   ```

2. **Générer des tests pour un autre répertoire (par exemple `Controllers`)** :

   ```bash
   php artisan generate:tests --directory=Controllers
   ```

3. **Générer des tests avec l'assertion par défaut `assertNotNull`** :

   ```bash
   php artisan generate:tests
   ```

4. **Générer des tests avec des dépendances mockées** :

   Si vous souhaitez mocker des dépendances dans vos tests, utilisez l'option `--mock` :

   ```bash
   php artisan generate:tests --mock="App\\Services\\SomeService"
   ```

5. **Activer les tests de performance** :

   Pour activer les tests de performance, utilisez l'option `--performance=true` :

   ```bash
   php artisan generate:tests --performance=true
   ```

## Options disponibles

- `--directory` : Le répertoire des classes à explorer (par défaut `Services`).
- `--response` : La réponse attendue pour les tests générés (par défaut `null`).
- `--assertion` : Le type d'assertion à utiliser pour les tests (par défaut `assertNotNull`).
- `--mock` : Les dépendances à mocker (par exemple `App\\Services\\SomeService`).
- `--performance` : Activer les tests de performance (`true`/`false`).


## Structure des tests générés

Les tests générés seront enregistrés dans le répertoire `tests/Unit/` et auront des noms basés sur les classes que vous avez annotées avec `@Testable`. Par exemple, si vous avez une classe `SomeService`, le test sera généré sous le nom `SomeServiceTest.php`.

Chaque méthode annotée avec `@Testable` générera un test avec la structure suivante :

```php
public function testMethodName()
{
    $instance = new SomeService();
    $result = $instance->methodName();

    // L'assertion choisie (par défaut assertNotNull)
    $this->assertEquals('Success', $result);
}
```
## Limitations et À venir

- Ce package génère des tests de base pour des méthodes simples. Les tests complexes, notamment ceux qui nécessitent une logique plus avancée, peuvent nécessiter une personnalisation manuelle.
- La prise en charge des tests de performance est encore limitée aux tests de base sur le temps d'exécution de la méthode.

---

## Contributions

Les contributions sont les bienvenues ! Si vous souhaitez ajouter des fonctionnalités ou corriger des bugs, veuillez ouvrir une **pull request** sur GitHub.

## Licence

Ce package est sous licence MIT. Consultez le fichier [LICENSE](LICENSE) pour plus de détails.

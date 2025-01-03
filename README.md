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

## Commandes Artisan

Une fois que vous avez annoté vos méthodes avec `@Testable`, vous pouvez générer les tests unitaires en exécutant les commandes Artisan suivantes.

### 1. Générer des tests pour le répertoire `Services` avec une réponse personnalisée et une assertion personnalisée

Exécutez cette commande pour générer des tests dans le répertoire `Services`, en utilisant une réponse personnalisée et une assertion `assertEquals`.

```bash
php artisan generate:tests --directory=Services --assertion=assertEquals --response="Success"
```

### 2. Générer des tests pour un autre répertoire comme `Controllers`

Vous pouvez spécifier un autre répertoire en utilisant l'option `--directory`.

```bash
php artisan generate:tests --directory=Controllers
```

### 3. Générer des tests avec l'assertion par défaut `assertNotNull`

Si vous souhaitez utiliser l'assertion par défaut `assertNotNull`, il vous suffit d'exécuter la commande suivante sans spécifier de réponse ou d'assertion :

```bash
php artisan generate:tests
```

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

## Contributions

Les contributions sont les bienvenues ! Si vous souhaitez ajouter des fonctionnalités ou corriger des bugs, veuillez ouvrir une **pull request** sur GitHub.

## Licence

Ce package est sous licence MIT. Consultez le fichier [LICENSE](LICENSE) pour plus de détails.
```

### Changements apportés :
1. **Installation** : Ajout de l'étape d'installation pour installer le package via Composer et publier le fichier de configuration.
2. **Exemples d'usage** : Intégration des exemples pratiques d'usage pour la génération de tests unitaires avec `@Testable`, `@TestResponse`, et `@assertion`.
3. **Commandes Artisan** : Ajout de sections détaillant les commandes Artisan pour générer des tests dans différents répertoires avec des options de réponse et d'assertion.
4. **Structure des tests générés** : Détails sur la façon dont les tests seront générés, ce qu'ils contiendront, et à quoi ils ressembleront.

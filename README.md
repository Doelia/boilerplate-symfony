# Boilerplate Symfony 6.4

- SF 6.4 + PHP 8.4
- FrankenPHP mode worker (avec fix BDD), Docker mode prod
- Configuration Monolog
- Modèle Healthcheck
- Config réseau : CORS, Trusted proxies
- PHP.ini (Memory limit 1G, Upload 2M)
- Outils de debug IP / Logs
- Outils de devs : Profiler, Maker
- Configuration PHPUnit, Tests HTTP via attribut `#[HttpTest]`
- Configuration PHPStan / Code Sniffer

## Installation
```
git clone https://github.com/doelia/boilerplate-symfony.git nom-du-projet
cd nom-du-projet
rm -rf .git
git init
```

## Post installation

- Vérifier que les dépendances sont sur les dernières versions : `composer outdated --direct`
- Mettre à jour le composer.lock `composer update`
- Retirer cette partie du README.md et compléter le reste.
 
### Formatage des erreurs
Si votre projet est une API, vous préférez des erreurs au format JSON plutôt que les pages HTML d'erreur de Symfony.
Dans le fichier `config/routes.yaml`, ajouter `format: json` : 
```
controllers:
    resource:
        path: ../src/Controller/
        namespace: App\Controller
    type: attribute
    format: json
```

Si c'est une app hybride, spécifier "format: json" pour les routes API :
```
#[Route('/api', format: 'json')]
```

###################################################################

# // Nom du projet

// Décrire l'objectif du projet

## Utilisation en local

Stack :
- PHP 8.4
- Symfony 6.4

Installer :
```
composer install
```

Lancer le serveur :
```
# Classique
symfony serve

# Avec FrankenPHP
APP_RUNTIME="Runtime\\FrankenPhpSymfony\\Runtime" frankenphp php-server --root=public -w public/index.php -l 127.0.0.1:8000 --watch="$(pwd)"
```

Tester le build/run docker en mode prod :
```
cd .cloud/local
docker compose up --build
```

### Tests

Tout vérifier (Phpunit, Lint, PHPStan...). - Conseillé avant de commit.
```
composer check
```

#### Phpunit
```
# Executer tous les tests
php bin/phpunit

# Executer un #[HttpTest] spécifique (Utiliser le name, ou class::method si pas de name)
php bin/phpunit --filter MainController::index
```

## Production

// Indiquer où est hébergé le projet + l'URL de prod.


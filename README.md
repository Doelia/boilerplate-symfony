# Boilerplate Symfony 7.4

- SF 7.4 + PHP 8.5
- FrankenPHP mode worker (avec fix BDD), Docker mode prod
- Configuration Monolog
- Modèle Healthcheck
- Système de cache pré-configuré
- Config réseau : CORS, Trusted proxies
- PHP.ini (Memory limit 1G, Upload 2M)
- Outils de debug IP / Logs
- Outils de devs : Profiler, Maker
- Configuration PHPUnit, Tests HTTP via attribut `#[HttpTest]`
- Configuration PHPStan / Code Sniffer

## Architecture DDD

Le code applicatif sous `src/` est organisé en trois zones :

- `Core/` — composants techniques transverses (debug, event listeners, attributs...), ne connaît aucun domaine métier
- `Shared/` — code partagé entre plusieurs domaines (services communs...)
- `Domains/<NomDuDomaine>/` — code propre à chaque domaine métier (ex. `Domains/Main`)

Si vous ne voulez pas du DDD, supprimez simplement le dossier Shared et utiliser Domains/Main comme unique domaine.

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
- Changer la valeur par défaut de la var d'env `DEBUG_SECRETPASS=xxx` dans le .env
- Retirer cette partie du README.md et compléter le reste.
- Choisir un seul mode de fonctionnement en local (docker ou sans docker) et supprimer l'autre partie. Préférer Docker si la stack est complexe (BDD, messenger, cache...), sinon sans docker est plus simple.
- Supprimez/décommentez les parties du docker-compose.yaml non utilisées
 
### Formatage des erreurs

Si votre projet est une API, vous préférez des erreurs au format JSON plutôt que les pages HTML d'erreur de Symfony.
Dans le fichier `config/routes.yaml`, ajouter `format: json` : 
```
controllers:
    ...
    # Uncomment to show error as JSON instead of Symfony default HTML error page
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
- PHP 8.5
- Symfony 7.4

### Avec docker (Recommandé)

Créer un fichier `.env.local` si besoin d'ajuster des variables du `.env`.

Composer install :
```
docker compose run app composer install
```

Lancer le serveur:
```
docker compose up
```

Puis naviguer sur http://127.0.0.1:8000

### Sans docker

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

### Tests

Si vous utilisez docker, entrez ces commandes après un `docker compose exec app bash`

Tout vérifier (Phpunit, Lint, PHPStan...). - Conseillé avant de commit.
```
composer check

# Fixer les erreurs de code style automatiquement
vendor/bin/phpcbf src
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


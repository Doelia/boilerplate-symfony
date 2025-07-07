<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Par défaut, FrankenPHP réutilise la connexion à la base de données entre les requêtes.
 * Ce n'est pas un comportement habituel pour PHP, l'écosystème n'est pas pret.
 * On ferme la connexion à chaque requête.
 *
 * L'autowire est en string pour que l'event listener fonctionne même s'il n'y a pas Doctrine dans le projet.
 */
#[AsEventListener]
final class FixFrankenDatabase
{
    /** @phpstan-ignore-next-line missingType.parameter */
    public function __construct(
        #[Autowire(service: 'doctrine.dbal.default_connection')] private $db,
    ) {
    }

    public function __invoke(TerminateEvent $event): void
    {
        if ($this->db) {
            $this->db->close();
        }
    }
}

<?php

namespace App\Tests\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\UseItem;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<UseItem>
 */
class DomainDependencyUseRule implements Rule
{
    public function getNodeType(): string
    {
        return UseItem::class;
    }

    /**
     * @param UseItem $node
     * @return IdentifierRuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // 1. La cible du use doit pointer vers un domaine, sinon rien à vérifier
        // (ex: App\Core\..., App\Shared\..., Vendor\Xxx, …)
        $targetFqn = (string) $node->name; // ex. "App\\Domains\\Users\\Something"
        if (!preg_match('#^App\\\\Domains\\\\([^\\\\]+)#', $targetFqn, $m2)) {
            return [];
        }
        $targetDomain = $m2[1];

        // 2. Récupère le namespace courant
        $currentNamespace = $scope->getNamespace() ?? '';

        // 3. Un fichier hors domaine (Core, Shared, ou racine App) ne doit jamais
        // dépendre d'un domaine : la dépendance ne doit remonter que dans un sens.
        if (!preg_match('#^App\\\\Domains\\\\([^\\\\]+)#', $currentNamespace, $m)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Interdiction d’importer une classe du domaine "%s" depuis en dehors des domaines (%s).',
                    $targetDomain,
                    $currentNamespace !== '' ? $currentNamespace : 'namespace global'
                ))
                    ->identifier('domainDependency.use')
                    ->line($node->getAttribute('startLine'))
                    ->build(),
            ];
        }
        $currentDomain = $m[1];

        // 4. Si domaines différents, erreur
        if ($targetDomain !== $currentDomain) {
            $message = sprintf(
                'Interdiction d’importer une classe du domaine "%s" depuis le domaine "%s".',
                $targetDomain,
                $currentDomain
            );

            return [
                RuleErrorBuilder::message($message)
                    ->identifier('domainDependency.use')
                    ->line($node->getAttribute('startLine'))
                    ->build(),
            ];
        }

        return [];
    }
}

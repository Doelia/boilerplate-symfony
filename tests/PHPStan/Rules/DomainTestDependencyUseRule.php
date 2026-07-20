<?php
namespace App\Tests\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\UseItem;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Interdit d'importer une classe appartenant à un namespace de test
 * (ex: App\Domains\Users\Tests\..., App\Tests\...) depuis du code "src"
 * (c'est-à-dire depuis un namespace qui n'est pas lui-même un namespace de test).
 *
 * @implements Rule<UseItem>
 */
class DomainTestDependencyUseRule implements Rule
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
        $targetFqn = (string) $node->name; // ex. "App\\Domains\\Users\\Tests\\Something"

        // 1. La cible du use doit pointer vers un namespace de test, sinon rien à vérifier.
        //    On détecte un segment "Tests" n'importe où dans le chemin.
        if (!preg_match('#(^|\\\\)Tests(\\\\|$)#', $targetFqn)) {
            return [];
        }

        // 2. Récupère le namespace courant du fichier qui fait le "use".
        $currentNamespace = $scope->getNamespace() ?? '';

        // 3. Si le fichier courant est lui-même dans un namespace de test,
        //    l'import est légitime (test qui dépend d'un autre test / d'une fixture de test).
        if (preg_match('#(^|\\\\)Tests(\\\\|$)#', $currentNamespace)) {
            return [];
        }

        // 4. Sinon, du code "src" importe du code de test : interdit.
        return [
            RuleErrorBuilder::message(sprintf(
                'Interdiction d’importer une classe de test "%s" depuis du code src (%s).',
                $targetFqn,
                $currentNamespace !== '' ? $currentNamespace : 'namespace global'
            ))
                ->identifier('domainDependency.testUse')
                ->line($node->getAttribute('startLine'))
                ->build(),
        ];
    }
}

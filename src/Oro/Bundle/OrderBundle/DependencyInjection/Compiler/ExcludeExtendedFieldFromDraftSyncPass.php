<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Excludes specified fields from the extended fields draft synchronization
 * for given entity classes, as they are handled by dedicated listeners.
 *
 * Usage in a bundle class:
 *   $container->addCompilerPass(new ExcludeExtendedFieldFromDraftSyncPass([
 *       [Order::class, 'warehouse'],
 *       [OrderLineItem::class, 'warehouse'],
 *   ]));
 */
class ExcludeExtendedFieldFromDraftSyncPass implements CompilerPassInterface
{
    private const string SERVICE_ID = 'oro_order.draft_session.extended_fields_provider';

    /**
     * @param list<array{string, string}> $exclusions List of [className, fieldName] pairs
     */
    public function __construct(
        private readonly array $exclusions,
    ) {
    }

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::SERVICE_ID);
        foreach ($this->exclusions as [$className, $fieldName]) {
            $definition->addMethodCall('addExcludedField', [$className, $fieldName]);
        }
    }
}

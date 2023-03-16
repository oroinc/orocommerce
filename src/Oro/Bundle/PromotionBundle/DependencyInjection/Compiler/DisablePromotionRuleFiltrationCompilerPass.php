<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Disables filtering services for promotions.
 * Replacing these filters is implemented at the database level in PromotionRepository::getAvailablePromotions.
 */
class DisablePromotionRuleFiltrationCompilerPass implements CompilerPassInterface
{
    /**
     * @return array
     */
    private function getDefinitions(): array
    {
        return [
            'oro_promotion.rule_filtration.enabled_decorator',
            'oro_promotion.rule_filtration.scope_decorator',
            'oro_promotion.rule_filtration.currency_decorator',
            'oro_promotion.rule_filtration.schedule_decorator'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        foreach ($this->getDefinitions() as $key) {
            if (!$container->hasDefinition($key)) {
                continue;
            }

            $container->removeDefinition($key);
        }
    }
}

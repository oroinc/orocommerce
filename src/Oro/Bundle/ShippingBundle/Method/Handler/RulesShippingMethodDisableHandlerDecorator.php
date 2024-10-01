<?php

namespace Oro\Bundle\ShippingBundle\Method\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Handles shipping rules when an integration is disabled.
 */
class RulesShippingMethodDisableHandlerDecorator implements ShippingMethodDisableHandlerInterface
{
    private ShippingMethodDisableHandlerInterface $handler;
    private ManagerRegistry $doctrine;
    private ShippingMethodProviderInterface $shippingMethodProvider;

    public function __construct(
        ShippingMethodDisableHandlerInterface $handler,
        ManagerRegistry $doctrine,
        ShippingMethodProviderInterface $shippingMethodProvider
    ) {
        $this->handler = $handler;
        $this->doctrine = $doctrine;
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    #[\Override]
    public function handleMethodDisable(string $methodId): void
    {
        $this->handler->handleMethodDisable($methodId);
        $configRules = $this->doctrine->getRepository(ShippingMethodsConfigsRule::class)
            ->getEnabledRulesByMethod($methodId);
        foreach ($configRules as $configRule) {
            if (!$this->configHasEnabledMethod($configRule, $methodId)) {
                $configRule->getRule()->setEnabled(false);
            }
        }
    }

    private function configHasEnabledMethod(ShippingMethodsConfigsRule $configRule, string $disabledMethodId): bool
    {
        $methodConfigs = $configRule->getMethodConfigs();
        foreach ($methodConfigs as $methodConfig) {
            $methodId = $methodConfig->getMethod();
            if ($methodId !== $disabledMethodId) {
                $method = $this->shippingMethodProvider->getShippingMethod($methodId);
                if (null !== $method && $method->isEnabled()) {
                    return true;
                }
            }
        }

        return false;
    }
}

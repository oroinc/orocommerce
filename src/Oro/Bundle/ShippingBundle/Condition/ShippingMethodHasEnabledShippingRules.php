<?php

namespace Oro\Bundle\ShippingBundle\Condition;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;

/**
 * Check if shipping method has shipping rules
 * Usage:
 * @shipping_method_has_enabled_shipping_rules: method_identifier
 */
class ShippingMethodHasEnabledShippingRules extends AbstractShippingMethodHasShippingRules
{
    /**
     * @var ShippingMethodsConfigsRuleRepository
     */
    private $repository;

    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    protected function getRulesByMethod($shippingMethodIdentifier)
    {
        return $this->repository->getEnabledRulesByMethod($shippingMethodIdentifier);
    }
}

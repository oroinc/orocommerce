<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

class OrderContextDataConverter implements ContextDataConverterInterface
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /** @param ScopeManager $scopeManager */
    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param Order $entity
     * {@inheritdoc}
     */
    public function getContextData($entity): array
    {
        if (!$this->supports($entity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Entity "%s" is not supported.', get_class($entity))
            );
        }

        return [
            self::SHIPPING_COST => $entity->getShippingCost(),
            self::LINE_ITEMS => $entity->getLineItems(),
            self::SUBTOTAL => $entity->getSubtotal(),
            self::SHIPPING_ADDRESS => $entity->getShippingAddress(),
            self::BILLING_ADDRESS => $entity->getBillingAddress(),
            self::SHIPPING_METHOD => $entity->getShippingMethod(),
            self::CUSTOMER => $entity->getCustomer(),
            self::CUSTOMER_USER => $entity->getCustomerUser(),
            self::CUSTOMER_GROUP => $entity->getCustomer()->getGroup(),
            self::CURRENCY => $entity->getCurrency(),
            self::CRITERIA => $this->scopeManager->getCriteria('promotion'),
        ];
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function supports($entity): bool
    {
        return $entity instanceof Order;
    }
}

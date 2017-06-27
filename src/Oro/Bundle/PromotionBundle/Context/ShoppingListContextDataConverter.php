<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListContextDataConverter implements ContextDataConverterInterface
{
    /**
     * @var CustomerUserRelationsProvider
     */
    private $relationsProvider;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @param CustomerUserRelationsProvider $relationsProvider
     * @param ScopeManager $scopeManager
     */
    public function __construct(CustomerUserRelationsProvider $relationsProvider, ScopeManager $scopeManager)
    {
        $this->relationsProvider = $relationsProvider;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param ShoppingList $entity
     * {@inheritdoc}
     */
    public function getContextData($entity): array
    {
        $customerUser = $entity->getCustomerUser();
        $customer = $this->relationsProvider->getCustomer($customerUser);
        $customerGroup = $this->relationsProvider->getCustomerGroup($customerUser);
        if (!$customerGroup && $entity->getCustomer()) {
            $customerGroup = $entity->getCustomer()->getGroup();
        }

        return [
            self::CRITERIA => $this->scopeManager->getCriteria('promotion'),
            self::CUSTOMER_USER => $entity->getCustomerUser(),
            self::CUSTOMER => $customer ?: $entity->getCustomer(),
            self::CUSTOMER_GROUP => $customerGroup,
            self::LINE_ITEMS => $this->getLineItems($entity),
            self::CURRENCY => 'USD' // TODO replace with customer user currency. Be aware of admin and cli calls
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof ShoppingList;
    }

    /**
     * @todo use discount line items, extract discount line items creation by SL line items and reuse in both converters
     * @param ShoppingList $entity
     * @return array
     */
    private function getLineItems(ShoppingList $entity)
    {
        return $entity->getLineItems()->toArray();
    }
}

<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderLineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class OrderContextDataConverter implements ContextDataConverterInterface
{
    /**
     * @var CustomerUserRelationsProvider
     */
    protected $relationsProvider;

    /**
     * @var OrderLineItemsToDiscountLineItemsConverter
     */
    protected $lineItemsConverter;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var SubtotalProviderInterface
     */
    protected $lineItemSubtotalProvider;

    /**
     * @param CustomerUserRelationsProvider $relationsProvider
     * @param OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter
     * @param UserCurrencyManager $userCurrencyManager
     * @param ScopeManager $scopeManager
     * @param SubtotalProviderInterface $lineItemSubtotalProvider
     */
    public function __construct(
        CustomerUserRelationsProvider $relationsProvider,
        OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter,
        UserCurrencyManager $userCurrencyManager,
        ScopeManager $scopeManager,
        SubtotalProviderInterface $lineItemSubtotalProvider
    ) {
        $this->relationsProvider = $relationsProvider;
        $this->lineItemsConverter = $lineItemsConverter;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->scopeManager = $scopeManager;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
    }

    /**
     * @param Order $entity
     * {@inheritdoc}
     */
    public function getContextData($entity): array
    {
        if (!$this->supports($entity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($entity))
            );
        }

        $customerUser = $entity->getCustomerUser();
        $customer = $this->relationsProvider->getCustomer($customerUser);
        $customerGroup = $this->relationsProvider->getCustomerGroup($customerUser);
        if (!$customerGroup && $entity->getCustomer()) {
            $customerGroup = $entity->getCustomer()->getGroup();
        }
        $subtotal = $this->lineItemSubtotalProvider->getSubtotal($entity);

        return [
            self::CUSTOMER_USER => $entity->getCustomerUser(),
            self::CUSTOMER => $customer ?: $entity->getCustomer(),
            self::CUSTOMER_GROUP => $customerGroup,
            self::LINE_ITEMS => $this->getLineItems($entity),
            self::SUBTOTAL => $subtotal->getAmount(),
            self::CURRENCY => $this->userCurrencyManager->getUserCurrency(),
            self::CRITERIA => $this->scopeManager->getCriteria('promotion'),
            self::BILLING_ADDRESS => $entity->getBillingAddress(),
            self::SHIPPING_ADDRESS => $entity->getShippingAddress(),
            self::SHIPPING_COST => $entity->getShippingCost(),
            self::SHIPPING_METHOD => $entity->getShippingMethod(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof Order;
    }

    /**
     * @param Order $entity
     * @return DiscountLineItem[]
     */
    protected function getLineItems(Order $entity)
    {
        return $this->lineItemsConverter->convert(
            $entity->getLineItems()->toArray()
        );
    }
}

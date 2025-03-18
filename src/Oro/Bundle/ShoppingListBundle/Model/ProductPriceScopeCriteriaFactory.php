<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderDTO;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * The total of the shopping list and product prices are calculated based on the current customer.
 */
class ProductPriceScopeCriteriaFactory implements ProductPriceScopeCriteriaFactoryInterface
{
    private CustomerUserRelationsProvider $customerRelationsProvider;

    public function __construct(
        private CustomerUserProvider $customerUserProvider,
        private ProductPriceScopeCriteriaFactoryInterface $inner
    ) {
    }

    public function create(
        Website $website = null,
        Customer $customer = null,
        $context = null,
        array $data = []
    ): ProductPriceScopeCriteriaInterface {
        return $this->inner->create($website, $customer, $context, $data);
    }

    public function setCustomerUserRelationsProvider(CustomerUserRelationsProvider $customerRelationsProvider): void
    {
        $this->customerRelationsProvider = $customerRelationsProvider;
    }

    public function createByContext($context, array $data = []): ProductPriceScopeCriteriaInterface
    {
        $criteria = $this->inner->createByContext($context, $data);

        $customerHolder = $this->getCustomerHolder($context);
        if ($customerHolder) {
            $customerUser = $this->customerUserProvider->getLoggedUser() ?: $customerHolder->getCustomerUser();

            $customer = null;
            if (!$customerUser) {
                $customer = $customerHolder->getCustomer();
            }

            if (!$customer) {
                $customer = $this->customerRelationsProvider->getCustomerIncludingEmpty($customerUser);
            }

            if ($context instanceof CustomerOwnerAwareInterface && $customer) {
                $criteria->setCustomer($customer);
            }
        }

        return $criteria;
    }

    private function getCustomerHolder($context): ?CustomerOwnerAwareInterface
    {
        if ($context instanceof ProductLineItemsHolderDTO) {
            $realLineItem = $context->getLineItems()->first();
            if ($realLineItem instanceof LineItem) {
                return $realLineItem->getShoppingList();
            }
            if ($realLineItem instanceof CheckoutLineItem) {
                return $realLineItem->getCheckout();
            }
        }

        if ($context instanceof CustomerOwnerAwareInterface) {
            return $context;
        }

        return null;
    }
}

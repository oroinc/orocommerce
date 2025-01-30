<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * The total of the shopping list and product prices are calculated based on the current customer.
 */
class ProductPriceScopeCriteriaFactory implements ProductPriceScopeCriteriaFactoryInterface
{
    public function __construct(
        private CustomerUserProvider $customerUserProvider,
        private ProductPriceScopeCriteriaFactoryInterface $inner
    ) {
    }

    #[\Override]
    public function create(
        ?Website  $website = null,
        ?Customer $customer = null,
                  $context = null,
        array     $data = []
    ): ProductPriceScopeCriteriaInterface {
        return $this->inner->create($website, $customer, $context, $data);
    }

    #[\Override]
    public function createByContext($context, array $data = []): ProductPriceScopeCriteriaInterface
    {
        $customerUser = $this->customerUserProvider->getLoggedUser();
        $criteria = $this->inner->createByContext($context, $data);
        if ($context instanceof CustomerOwnerAwareInterface && $customerUser) {
            $criteria->setCustomer($customerUser->getCustomer());
        }

        return $criteria;
    }
}

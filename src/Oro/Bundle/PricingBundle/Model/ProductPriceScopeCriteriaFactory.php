<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Allows to create ProductPriceScopeCriteria by either direct self::create() call or from a given context
 */
class ProductPriceScopeCriteriaFactory implements ProductPriceScopeCriteriaFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(
        Website $website = null,
        Customer $customer = null,
        $context = null,
        array $data = []
    ): ProductPriceScopeCriteriaInterface {
        $criteria = new ProductPriceScopeCriteria();
        $criteria->setWebsite($website);
        $criteria->setCustomer($customer);
        $criteria->setContext($context);
        foreach ($data as $key => $value) {
            $criteria->setData($key, $value);
        }

        return $criteria;
    }

    /**
     * {@inheritdoc}
     */
    public function createByContext($context, array $data = []): ProductPriceScopeCriteriaInterface
    {
        $website = null;
        if ($context instanceof WebsiteAwareInterface) {
            $website = $context->getWebsite();
        }

        $customer = null;
        if ($context instanceof CustomerOwnerAwareInterface) {
            $customer = $context->getCustomer();
        }

        return $this->create($website, $customer, $context, $data);
    }
}

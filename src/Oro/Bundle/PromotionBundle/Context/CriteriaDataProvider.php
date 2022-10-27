<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class CriteriaDataProvider
{
    /**
     * @var CustomerUserRelationsProvider
     */
    private $relationsProvider;

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    public function __construct(
        CustomerUserRelationsProvider $relationsProvider,
        WebsiteManager $websiteManager
    ) {
        $this->relationsProvider = $relationsProvider;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @param CustomerOwnerAwareInterface $entity
     * @return CustomerUser
     */
    public function getCustomerUser(CustomerOwnerAwareInterface $entity)
    {
        return $entity->getCustomerUser();
    }

    /**
     * @param CustomerOwnerAwareInterface $entity
     * @return null|Customer
     */
    public function getCustomer(CustomerOwnerAwareInterface $entity)
    {
        $customer = $this->relationsProvider->getCustomer($this->getCustomerUser($entity));
        if (!$customer) {
            $customer = $entity->getCustomer();
        }

        return $customer;
    }

    /**
     * @param CustomerOwnerAwareInterface $entity
     * @return null|CustomerGroup
     */
    public function getCustomerGroup(CustomerOwnerAwareInterface $entity)
    {
        $customer = $this->getCustomer($entity);
        $customerGroup = null;
        if ($customer) {
            $customerGroup = $customer->getGroup();
        }

        if (!$customerGroup) {
            $customerGroup = $this->relationsProvider->getCustomerGroup($this->getCustomerUser($entity));
        }

        return $customerGroup;
    }

    /**
     * @param WebsiteAwareInterface $entity
     * @return Website|null
     */
    public function getWebsite(WebsiteAwareInterface $entity)
    {
        return $entity->getWebsite() ?: $this->websiteManager->getCurrentWebsite();
    }
}

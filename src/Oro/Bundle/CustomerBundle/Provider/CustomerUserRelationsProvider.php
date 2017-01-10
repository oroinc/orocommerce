<?php

namespace Oro\Bundle\CustomerBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class CustomerUserRelationsProvider
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ConfigManager $configManager, DoctrineHelper $doctrineHelper)
    {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return null|Customer
     */
    public function getCustomer(CustomerUser $customerUser = null)
    {
        if ($customerUser) {
            return $customerUser->getCustomer();
        }

        return null;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return null|CustomerGroup
     */
    public function getCustomerGroup(CustomerUser $customerUser = null)
    {
        if ($customerUser) {
            $customer = $this->getCustomer($customerUser);
            if ($customer) {
                return $customer->getGroup();
            }
        } else {
            $anonymousGroupId = $this->configManager->get('oro_customer.anonymous_customer_group');

            if ($anonymousGroupId) {
                return $this->doctrineHelper->getEntityReference(
                    'OroCustomerBundle:CustomerGroup',
                    $anonymousGroupId
                );
            }
        }

        return null;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return null|Customer
     */
    public function getCustomerIncludingEmpty(CustomerUser $customerUser = null)
    {
        $customer = $this->getCustomer($customerUser);
        if (!$customer) {
            $customer = new Customer();
            $customer->setGroup($this->getCustomerGroup($customerUser));
        }
        
        return $customer;
    }
}

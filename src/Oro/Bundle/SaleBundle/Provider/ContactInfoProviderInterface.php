<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Model\ContactInfo;

/**
 * Interface for Get Contact Information Providers
 */
interface ContactInfoProviderInterface
{
    /**
     * @param CustomerUser|null $customerUser
     *
     * @return ContactInfo
     */
    public function getContactInfo(CustomerUser $customerUser = null);
}

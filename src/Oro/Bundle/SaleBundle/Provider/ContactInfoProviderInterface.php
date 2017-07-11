<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Model\ContactInfo;

interface ContactInfoProviderInterface
{
    /**
     * @param CustomerUser|null $customerUser
     *
     * @return ContactInfo
     */
    public function getContactInfo(CustomerUser $customerUser = null);
}

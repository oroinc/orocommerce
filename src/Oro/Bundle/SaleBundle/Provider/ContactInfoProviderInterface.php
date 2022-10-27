<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserInterface;
use Oro\Bundle\SaleBundle\Model\ContactInfo;

/**
 * Interface for Get Contact Information Providers
 */
interface ContactInfoProviderInterface
{
    /**
     * @param CustomerUserInterface|null $customerUser
     *
     * @return ContactInfo
     */
    public function getContactInfo(CustomerUserInterface $customerUser = null);
}

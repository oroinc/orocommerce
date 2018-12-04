<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Interface that generalize logic of single entry point for storing and getting data from the context
 * inside which we are working with consents
 */
interface ConsentContextProviderInterface
{
    /**
     * @return CustomerUser|null
     */
    public function getCustomerUser();

    /**
     * @return Website
     */
    public function getWebsite();

    /**
     * @return Scope|null
     */
    public function getScope();
}

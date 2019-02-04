<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Interface that generalize logic of single entry point for storing and getting data from the context
 * inside which we are working with consents
 */
interface ConsentContextProviderInterface
{
    /**
     * @param Website $website
     */
    public function setWebsite(Website $website);

    /**
     * @return Website
     */
    public function getWebsite();

    /**
     * @return Scope
     */
    public function getScope();
}

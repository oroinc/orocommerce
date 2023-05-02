<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class LoadProductVisibilityScopedData extends LoadProductVisibilityData
{
    /**
     * @var Website
     */
    protected $defaultWebsite;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->defaultWebsite = $this
            ->container
            ->get('oro_website.manager')
            ->getDefaultWebsite();

        parent::load($manager);
    }

    /**
     * {@inheritDoc}
     */
    protected function getScopeForProductVisibilities(): Scope
    {
        return $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getProductVisibilityScope($this->defaultWebsite);
    }

    /**
     * {@inheritDoc}
     */
    protected function getScopeForCustomerGroupVisibilities(CustomerGroup $customerGroup): Scope
    {
        return $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getCustomerGroupProductVisibilityScope($customerGroup, $this->defaultWebsite);
    }

    /**
     * {@inheritDoc}
     */
    protected function getScopeForCustomerVisibilities(Customer $customer): Scope
    {
        return $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getCustomerProductVisibilityScope($customer, $this->defaultWebsite);
    }
}

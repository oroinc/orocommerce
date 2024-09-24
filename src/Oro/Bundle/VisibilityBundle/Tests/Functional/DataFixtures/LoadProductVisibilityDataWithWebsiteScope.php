<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadProductVisibilityDataWithWebsiteScope extends LoadProductVisibilityData
{
    #[\Override]
    protected function getScopeForProductVisibilities(): Scope
    {
        $website = $this->container->get('oro_website.manager')->getDefaultWebsite();
        return $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getProductVisibilityScope($website);
    }

    #[\Override]
    protected function getScopeForCustomerGroupVisibilities(CustomerGroup $customerGroup): Scope
    {
        $website = $this->container->get('oro_website.manager')->getDefaultWebsite();
        return $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getCustomerGroupProductVisibilityScope($customerGroup, $website);
    }

    #[\Override]
    protected function getScopeForCustomerVisibilities(Customer $customer): Scope
    {
        $website = $this->container->get('oro_website.manager')->getDefaultWebsite();
        return $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getCustomerProductVisibilityScope($customer, $website);
    }
}

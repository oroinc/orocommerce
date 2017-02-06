<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class LoadProductVisibilityScopedData extends LoadProductVisibilityData
{
    /**
     * @var Website
     */
    protected $defaultWebsite;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->defaultWebsite = $this
            ->container
            ->get('oro_website.manager')
            ->getDefaultWebsite();

        parent::load($manager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopeForProductVisibilities()
    {
        return $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getProductVisibilityScope($this->defaultWebsite);
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopeForCustomerGroupVisibilities(CustomerGroup $customerGroup)
    {
        return $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getCustomerGroupProductVisibilityScope($customerGroup, $this->defaultWebsite);
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopeForCustomerVisibilities(Customer $customer)
    {
        return $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getCustomerProductVisibilityScope($customer, $this->defaultWebsite);
    }
}

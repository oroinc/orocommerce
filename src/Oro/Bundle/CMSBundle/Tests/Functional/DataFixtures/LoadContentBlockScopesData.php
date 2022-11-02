<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadContentBlockScopesData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        /** @var Customer $secondCustomer */
        $secondCustomer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1);

        $scope1 = $this->createScopeWithCustomer($customer);
        $this->addReference('content_block1_scope1', $scope1);

        $scope2 = $this->createScopeWithCustomer($secondCustomer);
        $this->addReference('content_block1_scope2', $scope2);

        /** @var ContentBlock $contentBlock1 */
        $contentBlock1 = $this->getReference('content_block_1');
        $contentBlock1->addScope($scope1);
        $contentBlock1->addScope($scope2);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadContentBlockData::class,
            LoadCustomers::class
        ];
    }

    /**
     * @param Customer $customer
     * @return Scope
     */
    protected function createScopeWithCustomer(Customer $customer)
    {
        return $this->container->get('oro_scope.scope_manager')
            ->findOrCreate('web_content', ['customer' => $customer]);
    }
}

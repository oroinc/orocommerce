<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadFilterProductVisibilityData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductData::class,
            LoadCustomers::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $manager->getRepository(Website::class)->find(1);

        /**
         *  All customer users:
         *
         *  CustomerUser CustomerUser
         *  customer.orphan
         *  customer.level_1
         *  customer.level_1.1
         *  customer.level_1.1.1
         *  customer.level_1.1.2
         *  customer.level_1.2
         *  customer.level_1.2.1
         *  customer.level_1.2.1.1
         *  customer.level_1.3
         *  customer.level_1.3.1
         *  customer.level_1.3.1.1
         *  customer.level_1.4
         *  customer.level_1.4.1
         *  customer.level_1.4.1.1
         *  customer.level_1_1
         **/

        $data = [
            'customer.level_1' => 'visible',
            'customer.level_1.1' => 'visible',
            'customer.level_1.1.1' => 'visible',
            'customer.level_1.2' => 'hidden',
            'customer.level_1.2.1' => 'hidden',
        ];

        foreach ($data as $customerReference => $visibility) {
            /** @var Customer $customer */
            $customer = $this->getReference($customerReference);
            $scope = $this->container->get('oro_visibility.provider.visibility_scope_provider')
                ->getCustomerProductVisibilityScope($customer, $website);

            $customerProductVisibility = new CustomerProductVisibility();
            $customerProductVisibility->setProduct($product)
                ->setScope($scope)
                ->setVisibility($visibility);

            $manager->persist($customerProductVisibility);
        }

        $manager->flush();
    }
}

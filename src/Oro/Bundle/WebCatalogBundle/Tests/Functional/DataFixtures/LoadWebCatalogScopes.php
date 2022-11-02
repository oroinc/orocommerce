<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class LoadWebCatalogScopes extends AbstractFixture implements DependentFixtureInterface
{
    const SCOPE1 = 'web_catalog.scope1';
    const SCOPE2 = 'web_catalog.scope2';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadContentVariantsData::class,
            LoadCustomers::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        /** @var ContentVariant $contentVariant */
        $contentVariant = $this->getReference(LoadContentVariantsData::CUSTOMER_VARIANT);
        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $scope1 = new Scope();
        $scope1->setWebCatalog($webCatalog);
        $contentVariant->addScope($scope1);
        $manager->persist($scope1);
        $this->addReference(self::SCOPE1, $scope1);

        $scope2 = new Scope();
        $scope2->setWebCatalog($webCatalog);
        $scope2->setCustomer($customer);
        $manager->persist($scope2);
        $this->addReference(self::SCOPE2, $scope2);

        $manager->flush();
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\ScopeBundle\Entity\Scope;
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
            LoadWebCatalogData::class,
            LoadAccounts::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        /** @var Account $account */
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);

        $scope1 = new Scope();
        $scope1->setWebCatalog($webCatalog);
        $manager->persist($scope1);
        $this->addReference(self::SCOPE1, $scope1);

        $scope2 = new Scope();
        $scope2->setWebCatalog($webCatalog);
        $scope2->setAccount($account);
        $manager->persist($scope2);
        $this->addReference(self::SCOPE2, $scope2);

        $manager->flush();
    }
}

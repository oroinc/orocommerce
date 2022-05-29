<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;

abstract class AbstractProductResolvedCacheBuilderTest extends WebTestCase
{
    use CatalogTrait;

    protected const ROOT = 'root';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var Scope */
    protected $scope;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadOrganization::class, LoadCategoryVisibilityData::class]);

        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->doctrine = self::getContainer()->get('doctrine');
    }

    protected function tearDown(): void
    {
        $this->doctrine->getManager()->clear();
        parent::tearDown();
    }

    protected function getOrganization(): Organization
    {
        return $this->getReference(LoadOrganization::ORGANIZATION);
    }
}

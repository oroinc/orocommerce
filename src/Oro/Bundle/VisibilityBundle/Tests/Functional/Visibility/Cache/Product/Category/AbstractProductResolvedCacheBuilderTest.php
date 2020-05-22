<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\OrganizationBundle\Tests\Functional\OrganizationTrait;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;

abstract class AbstractProductResolvedCacheBuilderTest extends WebTestCase
{
    use OrganizationTrait, CatalogTrait;

    const ROOT = 'root';

    /** @var Registry */
    protected $registry;

    /** @var Scope */
    protected $scope;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadCategoryVisibilityData::class,
        ]);

        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->registry = $this->client->getContainer()->get('doctrine');
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('doctrine')->getManager()->clear();
        parent::tearDown();
    }
}

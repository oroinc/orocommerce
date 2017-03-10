<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapProductByVisibilityListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityFallbackCategoryData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityScopedData;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * @dbIsolationPerTest
 */
class RestrictSitemapProductByVisibilityListenerTest extends WebTestCase
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ContainerAwareEventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->eventDispatcher = $this->getContainer()->get('event_dispatcher');

        $this->queryBuilder = $this->doctrineHelper->getEntityManagerForClass(Product::class)->createQueryBuilder();
        $this->queryBuilder
            ->select('product.id')
            ->from(Product::class, 'product');
    }

    public function testNotVisibleFiltered()
    {
        $this->loadFixtures([LoadProductVisibilityScopedData::class]);
        $this->getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();

        $resultBeforeRestriction = $this->queryBuilder->getQuery()->getArrayResult();
        $this->assertCount(8, $resultBeforeRestriction);

        $listener = new RestrictSitemapProductByVisibilityListener(
            $this->doctrineHelper,
            $this->configManager
        );
        $listener->setProductVisibilitySystemConfigurationPath('oro_visibility.product_visibility');
        $listener->setCategoryVisibilitySystemConfigurationPath('oro_visibility.category_visibility');
        $listener->setVisibilityScopeProvider(
            $this->getContainer()->get('oro_visibility.provider.visibility_scope_provider')
        );

        $version = '1';
        $website =  $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $event = new RestrictSitemapEntitiesEvent($this->queryBuilder, $version, $website);
        $listener->restrictQueryBuilder($event);

        $resultAfterRestriction = $this->queryBuilder->getQuery()->getArrayResult();
        $this->assertCount(6, $resultAfterRestriction);
    }

    /**
     * @todo after fix of BB-8133, should be implemented for both editions
     * @group CommunityEdition
     */
    public function testNotVisibleFallbackCategoryFiltered()
    {
        $this->loadFixtures([LoadProductVisibilityFallbackCategoryData::class]);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();

        $resultBeforeRestriction = $this->queryBuilder->getQuery()->getArrayResult();
        $this->assertCount(8, $resultBeforeRestriction);

        $listener = new RestrictSitemapProductByVisibilityListener(
            $this->doctrineHelper,
            $this->configManager
        );
        $listener->setProductVisibilitySystemConfigurationPath('oro_visibility.product_visibility');
        $listener->setCategoryVisibilitySystemConfigurationPath('oro_visibility.category_visibility');
        $listener->setVisibilityScopeProvider(
            $this->getContainer()->get('oro_visibility.provider.visibility_scope_provider')
        );

        $version = '1';
        $website =  $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $event = new RestrictSitemapEntitiesEvent($this->queryBuilder, $version, $website);
        $listener->restrictQueryBuilder($event);

        $resultAfterRestriction = $this->queryBuilder->getQuery()->getArrayResult();
        $this->assertCount(5, $resultAfterRestriction);
    }
}

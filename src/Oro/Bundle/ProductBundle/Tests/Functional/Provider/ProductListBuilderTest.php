<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\HttpFoundation\Request;

class ProductListBuilderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ProductListBuilder $builder;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        self::getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([
            '@OroProductBundle/Tests/Functional/DataFixtures/frontend_product_grid_pager_fixture.yml'
        ]);
        self::getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );

        $this->builder = self::getContainer()->get('oro_product.tests.product_list_builder');
    }

    public function testGetRelatedProductsByIds()
    {
        for ($i = 10; $i < 40; $i++) {
            $productIds[] = $this->getReference(sprintf('product%d', $i))->getId();
        }

        $productViews = $this->builder->getProductsByIds('related_products', $productIds);

        self::assertCount(30, $productViews);
    }
}

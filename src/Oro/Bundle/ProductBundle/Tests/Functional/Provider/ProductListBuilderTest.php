<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

class ProductListBuilderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?ProductListBuilder $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->builder = self::getContainer()->get('oro_product.tests.product_list_builder');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->builder);
        parent::tearDown();
    }

    /**
     * covers BB-21695
     */
    public function testGetRelatedProductsByIds()
    {
        $this->loadFixtures([
            new class implements FixtureInterface, ContainerAwareInterface, DependentFixtureInterface {
                use ContainerAwareTrait;

                /**
                 * {@inheritdoc}
                 */
                public function getDependencies()
                {
                    return [
                        '@OroProductBundle/Tests/Functional/DataFixtures/frontend_product_grid_pager_fixture.yml',
                    ];
                }

                /**
                 * {@inheritdoc}
                 */
                public function load(ObjectManager $manager)
                {
                    $this->container->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
                    $this->container->get('event_dispatcher')->dispatch(
                        new ReindexationRequestEvent([Product::class], [], [], false),
                        ReindexationRequestEvent::EVENT_NAME
                    );
                }
            },
        ]);

        for ($i = 10; $i < 40; $i++) {
            $productIds[] = $this->getReference(sprintf('product%d', $i))->getId();
        }

        $productViews = $this->builder->getProductsByIds('related_products', $productIds);

        self::assertCount(30, $productViews);
    }
}

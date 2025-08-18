<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SearchBundle\Engine\Orm;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

class ProductSearchTest extends FrontendRestJsonApiTestCase
{
    use WebsiteSearchExtensionTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product.yml',
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/product_search_orders.yml'
        ]);

        $configManager = self::getConfigManager();
        $configManager->set('oro_order.enable_purchase_history', true);
        $configManager->flush();

        self::reindexProductData();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_order.enable_purchase_history', false);
        $configManager->flush();

        parent::tearDown();
    }

    #[\Override]
    protected function postFixtureLoad(): void
    {
        parent::postFixtureLoad();

        /** @var Order $order1 */
        $order1 = $this->getReference('order1');
        /** @var Order $order2 */
        $order2 = $this->getReference('order2');
        $order1->setCreatedAt(new \DateTime('2018-02-15 10:30:00', new \DateTimeZone('UTC')));
        $order2->setCreatedAt(new \DateTime('2018-10-05 10:30:00', new \DateTimeZone('UTC')));
        $this->getEntityManager()->flush();
    }

    private function isOrmEngine(): bool
    {
        return Orm::ENGINE_NAME === self::getContainer()->get('oro_website_search.engine.parameters')->getEngineName();
    }

    public function testOrderedAt(): void
    {
        /** @var \DateTime $orderedAt */
        $orderedAt = $this->getReference('order1')->getCreatedAt();

        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'sku = "PSKU1"']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productsearch',
                        'id'         => '<toString(@product1->id)>',
                        'attributes' => [
                            'orderedAt' => $orderedAt->format('Y-m-d\TH:i:s\Z')
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByOrderedAt(): void
    {
        /** @var \DateTime $orderedAt */
        $orderedAt = $this->getReference('order2')->getCreatedAt();

        $fromOrderedAt = clone $orderedAt;
        $fromOrderedAt->sub(new \DateInterval('PT1S'));

        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => sprintf('orderedAt > "%s"', $fromOrderedAt->format('Y-m-d H:i:s'))]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productsearch',
                        'id'         => '<toString(@product3->id)>',
                        'attributes' => [
                            'orderedAt' => $orderedAt->format('Y-m-d\TH:i:s\Z')
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortByOrderedAtAsc(): void
    {
        /** @var \DateTime $orderedAt1 */
        $orderedAt1 = $this->getReference('order1')->getCreatedAt();
        /** @var \DateTime $orderedAt2 */
        $orderedAt2 = $this->getReference('order2')->getCreatedAt();

        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['sort' => 'orderedAt', 'filter' => ['searchQuery' => 'orderedAt exists']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productsearch',
                        'id'         => '<toString(@product1->id)>',
                        'attributes' => [
                            'orderedAt' => $orderedAt1->format('Y-m-d\TH:i:s\Z')
                        ]
                    ],
                    [
                        'type'       => 'productsearch',
                        'id'         => '<toString(@product3->id)>',
                        'attributes' => [
                            'orderedAt' => $orderedAt2->format('Y-m-d\TH:i:s\Z')
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortByOrderedAtDesc(): void
    {
        /** @var \DateTime $orderedAt1 */
        $orderedAt1 = $this->getReference('order1')->getCreatedAt();
        /** @var \DateTime $orderedAt2 */
        $orderedAt2 = $this->getReference('order2')->getCreatedAt();

        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['sort' => '-orderedAt', 'filter' => ['searchQuery' => 'orderedAt exists']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productsearch',
                        'id'         => '<toString(@product3->id)>',
                        'attributes' => [
                            'orderedAt' => $orderedAt2->format('Y-m-d\TH:i:s\Z')
                        ]
                    ],
                    [
                        'type'       => 'productsearch',
                        'id'         => '<toString(@product1->id)>',
                        'attributes' => [
                            'orderedAt' => $orderedAt1->format('Y-m-d\TH:i:s\Z')
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByOrderedAt(): void
    {
        /** @var \DateTime $orderedAt1 */
        $orderedAt1 = $this->getReference('order1')->getCreatedAt();
        /** @var \DateTime $orderedAt2 */
        $orderedAt2 = $this->getReference('order2')->getCreatedAt();

        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'orderedAt count'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'orderedAtCount' => [
                            ['value' => $orderedAt1->format('Y-m-d\TH:i:s\Z'), 'count' => 1],
                            ['value' => $orderedAt2->format('Y-m-d\TH:i:s\Z'), 'count' => 1]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testMinByOrderedAt(): void
    {
        if (!$this->isOrmEngine()) {
            $this->markTestSkipped('Can be tested only with ORM search engine');
        }

        /** @var \DateTime $orderedAt */
        $orderedAt = $this->getReference('order1')->getCreatedAt();

        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'orderedAt min'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'orderedAtMin' => $orderedAt->format('Y-m-d\TH:i:s\Z')
                    ]
                ]
            ],
            $response
        );
    }

    public function testMaxByOrderedAt(): void
    {
        if (!$this->isOrmEngine()) {
            $this->markTestSkipped('Can be tested only with ORM search engine');
        }

        /** @var \DateTime $orderedAt */
        $orderedAt = $this->getReference('order2')->getCreatedAt();

        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'orderedAt max'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'orderedAtMax' => $orderedAt->format('Y-m-d\TH:i:s\Z')
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByOrderedAtWhenNoProductsWithThisValue(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'orderedAt count', 'searchQuery' => 'orderedAt notexists'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        self::assertArrayNotHasKey('meta', self::jsonToArray($response->getContent()));
    }

    public function testMinByOrderedAtWhenNoProductsWithThisValue(): void
    {
        if (!$this->isOrmEngine()) {
            $this->markTestSkipped('Can be tested only with ORM search engine');
        }

        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'orderedAt min', 'searchQuery' => 'orderedAt notexists'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'orderedAtMin' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testMaxByOrderedAtWhenNoProductsWithThisValue(): void
    {
        if (!$this->isOrmEngine()) {
            $this->markTestSkipped('Can be tested only with ORM search engine');
        }

        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'orderedAt max', 'searchQuery' => 'orderedAt notexists'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'orderedAtMax' => null
                    ]
                ]
            ],
            $response
        );
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM\PreviouslyPurchasedFeatureTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

class ProductSearchTest extends FrontendRestJsonApiTestCase
{
    use WebsiteSearchExtensionTrait;
    use PreviouslyPurchasedFeatureTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml',
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/product_search_orders.yml'
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();

        /** @var Order $order1 */
        $order1 = $this->getReference('order1');
        /** @var Order $order2 */
        $order2 = $this->getReference('order2');
        $order1->setCreatedAt(new \DateTime('2018-02-15 10:30:00', new \DateTimeZone('UTC')));
        $order2->setCreatedAt(new \DateTime('2018-10-05 10:30:00', new \DateTimeZone('UTC')));
        $this->getEntityManager()->flush();

        $this->enablePreviouslyPurchasedFeature();
        $this->reindexProductData();
    }

    public function testOrderedAt()
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

    public function testFilterByOrderedAt()
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

    public function testSortByOrderedAtAsc()
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

    public function testSortByOrderedAtDesc()
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

    public function testCountByOrderedAt()
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

    public function testMinByOrderedAt()
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

    public function testMaxByOrderedAt()
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

    public function testCountByOrderedAtWhenNoProductsWithThisValue()
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

    public function testMinByOrderedAtWhenNoProductsWithThisValue()
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

    public function testMaxByOrderedAtWhenNoProductsWithThisValue()
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

    /**
     * @return bool
     */
    private function isOrmEngine()
    {
        return \Oro\Bundle\SearchBundle\Engine\Orm::ENGINE_NAME === $this->getSearchEngine();
    }

    /**
     * @return string
     */
    private function getSearchEngine()
    {
        return self::getContainer()
            ->get('oro_website_search.engine.parameters')
            ->getEngineName();
    }
}

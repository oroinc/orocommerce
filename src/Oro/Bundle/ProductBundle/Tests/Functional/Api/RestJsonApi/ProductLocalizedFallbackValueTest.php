<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Component\MessageQueue\Client\MessagePriority;

/**
 * @dbIsolationPerTest
 */
class ProductLocalizedFallbackValueTest extends RestJsonApiTestCase
{
    use MessageQueueAssertTrait;
    use DefaultWebsiteIdTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getOptionalListenerManager()->enableListeners([
            'oro_product.event_listener.website_search_reindex_product_kit',
            'oro_product.event_listener.search_product_kit'
        ]);

        $this->loadFixtures([
            '@OroProductBundle/Tests/Functional/Api/DataFixtures/product.yml'
        ]);
    }

    public function testUpdateKitProductName(): void
    {
        $response = $this->patch(
            ['entity' => 'productnames', 'id' => '<toString(@product_kit1_name->id)>'],
            [
                'data' => [
                    'type' => 'productnames',
                    'id' => '<toString(@product_kit1_name->id)>',
                    'attributes' => [
                        'fallback' => null,
                        'string' => 'An Updated Kit Name'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product_kit1->id)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains('update_kit_product_name.yml', $response);

        /** @var ProductName $productName */
        $productName = $this->getReference('product_kit1_name');
        $product = $productName->getProduct();

        self::assertEquals('An Updated Kit Name', $product->getDenormalizedDefaultName());
        self::assertAllMessagesSent([
            [
                'topic' => IndexEntitiesByIdTopic::getName(),
                'message' => [
                    'class' => Product::class,
                    'entityIds' => [$product->getId() => $product->getId()]
                ]
            ], [
                'topic' => WebsiteSearchReindexTopic::getName(),
                'message' => [
                    'class' => [Product::class],
                    'granulize' => true,
                    'context' => [
                        'websiteIds' => [self::getDefaultWebsiteId()],
                        'entityIds' => [$product->getId()],
                    ]
                ]
            ]
        ]);

        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
        self::assertMessageSentWithPriority(IndexEntitiesByIdTopic::getName(), MessagePriority::NORMAL);
    }

    public function testUpdateSimpleProductNameNotRelatedToProductKit(): void
    {
        $response = $this->patch(
            ['entity' => 'productnames', 'id' => '<toString(@product5_name->id)>'],
            [
                'data' => [
                    'type' => 'productnames',
                    'id' => '<toString(@product5_name->id)>',
                    'attributes' => [
                        'fallback' => null,
                        'string' => 'An Updated Name'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product5->id)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains('update_product_name.yml', $response);

        /** @var ProductName $name */
        $productName = $this->getReference('product5_name');
        $product = $productName->getProduct();

        self::assertEquals('An Updated Name', $product->getDenormalizedDefaultName());
        self::assertAllMessagesSent([
            [
                'topic' => IndexEntitiesByIdTopic::getName(),
                'message' => [
                    'class' => Product::class,
                    'entityIds' => [$product->getId() => $product->getId()]
                ]
            ], [
                'topic' => WebsiteSearchReindexTopic::getName(),
                'message' => [
                    'class' => [Product::class],
                    'granulize' => true,
                    'context' => [
                        'websiteIds' => [self::getDefaultWebsiteId()],
                        'entityIds' => [$product->getId()],
                    ]
                ]
            ]
        ]);

        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
        self::assertMessageSentWithPriority(IndexEntitiesByIdTopic::getName(), MessagePriority::NORMAL);
    }

    public function testUpdateSimpleProductNameRelatedToProductKit(): void
    {
        $response = $this->patch(
            ['entity' => 'productnames', 'id' => '<toString(@product1_name->id)>'],
            [
                'data' => [
                    'type' => 'productnames',
                    'id' => '<toString(@product1_name->id)>',
                    'attributes' => [
                        'fallback' => null,
                        'string' => 'An Updated Simple Name'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product1->id)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains('update_product_name_related_to_kit_product.yml', $response);

        /** @var ProductName $productName */
        $productName = $this->getReference('product1_name');
        $product = $productName->getProduct();

        $kit1 = $this->getReference('product_kit1');
        $kit2 = $this->getReference('product_kit2');
        $kit3 = $this->getReference('product_kit3');

        self::assertEquals('An Updated Simple Name', $product->getDenormalizedDefaultName());
        self::assertAllMessagesSent([
            [
                'topic' => IndexEntitiesByIdTopic::getName(),
                'message' => [
                    'class' => Product::class,
                    'entityIds' => [
                        $product->getId() => $product->getId(),
                        $kit1->getId() => $kit1->getId(),
                        $kit2->getId() => $kit2->getId(),
                        $kit3->getId() => $kit3->getId(),
                    ]
                ]
            ], [
                'topic' => WebsiteSearchReindexTopic::getName(),
                'message' => [
                    'class' => [Product::class],
                    'granulize' => true,
                    'context' => [
                        'websiteIds' => [self::getDefaultWebsiteId()],
                        'entityIds' => [
                            $kit1->getId(),
                            $kit2->getId(),
                            $kit3->getId(),
                            $product->getId(),
                        ],
                    ]
                ]
            ]
        ]);

        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
        self::assertMessageSentWithPriority(IndexEntitiesByIdTopic::getName(), MessagePriority::NORMAL);
    }

    public function testUpdateSimpleProductLocalizedDescription(): void
    {
        $response = $this->patch(
            ['entity' => 'productdescriptions', 'id' => '<toString(@product1_es_description->id)>'],
            [
                'data' => [
                    'type' => 'productdescriptions',
                    'id' => '<toString(@product1_es_description->id)>',
                    'attributes' => [
                        'fallback' => null,
                        'wysiwyg' => [
                            'value' => '<div>An Sentence of Updated ES Description.</div>',
                            'style' => null,
                            'properties' => null
                        ]
                    ],
                    'relationships' => [
                        'localization' => [
                            'data' => ['type' => 'localizations', 'id' => '<toString(@es->id)>']
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product1->id)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains('update_product_description.yml', $response);

        /** @var ProductDescription $productDesc */
        $productDesc = $this->getReference('product1_es_description');
        $product = $productDesc->getProduct();

        $kit1 = $this->getReference('product_kit1');
        $kit2 = $this->getReference('product_kit2');
        $kit3 = $this->getReference('product_kit3');

        self::assertEquals('<div>An Sentence of Updated ES Description.</div>', (string)$productDesc);
        self::assertAllMessagesSent([
            [
                'topic' => IndexEntitiesByIdTopic::getName(),
                'message' => [
                    'class' => Product::class,
                    'entityIds' => [
                        $product->getId() => $product->getId(),
                        $kit1->getId() => $kit1->getId(),
                        $kit2->getId() => $kit2->getId(),
                        $kit3->getId() => $kit3->getId(),
                    ]
                ]
            ], [
                'topic' => WebsiteSearchReindexTopic::getName(),
                'message' => [
                    'class' => [Product::class],
                    'granulize' => true,
                    'context' => [
                        'websiteIds' => [self::getDefaultWebsiteId()],
                        'entityIds' => [
                            $kit1->getId(),
                            $kit2->getId(),
                            $kit3->getId(),
                            $product->getId(),
                        ],
                    ]
                ]
            ]
        ]);

        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
        self::assertMessageSentWithPriority(IndexEntitiesByIdTopic::getName(), MessagePriority::NORMAL);
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Component\MessageQueue\Client\MessagePriority;

class ProductLocalizedFallbackValueTest extends RestJsonApiTestCase
{
    use MessageQueueAssertTrait;
    use DefaultWebsiteIdTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/DataFixtures/product.yml',
        ]);
    }

    public function testUpdateProductName(): void
    {
        $response = $this->patch(
            ['entity' => 'productnames', 'id' => '<toString(@product1_name->id)>'],
            [
                'data' => [
                    'type' => 'productnames',
                    'id' => '<toString(@product1_name->id)>',
                    'attributes' => [
                        'fallback' => null,
                        'string' => 'An Updated Name'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product1->id)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains('update_product_name.yml', $response);

        /** @var ProductName $productName */
        $productName = $this->getReference('product1_name');
        self::assertEquals('An Updated Name', $productName->getProduct()->getDenormalizedDefaultName());
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            [
                'class' => [Product::class],
                'granulize' => true,
                'context' => [
                    'websiteIds' => [self::getDefaultWebsiteId()],
                    'entityIds' => [$productName->getProduct()->getId()],
                ]
            ]
        );
        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
    }

    public function testUpdateProductDescription(): void
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
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            [
                'class' => [Product::class],
                'granulize' => true,
                'context' => [
                    'websiteIds' => [self::getDefaultWebsiteId()],
                    'entityIds' => [$productDesc->getProduct()->getId()],
                ],
            ]
        );
        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
    }
}

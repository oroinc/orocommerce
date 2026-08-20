<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;

/**
 * productimagetypes API actions must trigger a product reindex.
 *
 * @dbIsolationPerTest
 */
class ProductImageTypeTest extends RestJsonApiTestCase
{
    use DefaultWebsiteIdTestTrait;
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductData::class]);
    }

    private function getProductImage(string $productReference): ProductImage
    {
        /** @var Product $product */
        $product = $this->getReference($productReference);

        return $product->getImages()->first();
    }

    private function assertProductReindexed(Product $product): void
    {
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            [
                'class' => [Product::class],
                'granulize' => true,
                'context' => [
                    'entityIds' => [$product->getId()],
                    'websiteIds' => [self::getDefaultWebsiteId()],
                    AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image'],
                ],
            ]
        );
    }

    public function testCreateProductImageType(): void
    {
        $productImage = $this->getProductImage(LoadProductData::PRODUCT_1);
        $product = $productImage->getProduct();

        $this->post(
            ['entity' => 'productimagetypes'],
            [
                'data' => [
                    'type' => 'productimagetypes',
                    'attributes' => [
                        'productImageTypeType' => ProductImageType::TYPE_ADDITIONAL,
                    ],
                    'relationships' => [
                        'productImage' => [
                            'data' => ['type' => 'productimages', 'id' => (string)$productImage->getId()],
                        ],
                    ],
                ],
            ]
        );

        $this->assertProductReindexed($product);
    }

    public function testUpdateProductImageType(): void
    {
        $productImage = $this->getProductImage(LoadProductData::PRODUCT_1);
        $product = $productImage->getProduct();
        $type = $productImage->getTypes()->first();

        $newType = $type->getType() === ProductImageType::TYPE_MAIN
            ? ProductImageType::TYPE_ADDITIONAL
            : ProductImageType::TYPE_MAIN;

        $this->patch(
            ['entity' => 'productimagetypes', 'id' => (string)$type->getId()],
            [
                'data' => [
                    'type' => 'productimagetypes',
                    'id' => (string)$type->getId(),
                    'attributes' => [
                        'productImageTypeType' => $newType,
                    ],
                ],
            ]
        );

        $this->assertProductReindexed($product);
    }

    public function testDeleteProductImageType(): void
    {
        $productImage = $this->getProductImage(LoadProductData::PRODUCT_1);
        $product = $productImage->getProduct();
        $type = $productImage->getTypes()->first();

        $this->delete(['entity' => 'productimagetypes', 'id' => (string)$type->getId()]);

        $this->assertProductReindexed($product);
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Search;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\EventListener\Search\ReindexParentConfigurableProductListener;
use Oro\Bundle\SearchBundle\Utils\IndexationEntitiesContainer;
use Oro\Component\Testing\Unit\EntityTrait;

class ReindexParentConfigurableProductListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var IndexationEntitiesContainer */
    private $changedEntities;

    /** @var ReindexParentConfigurableProductListener */
    private $listener;

    protected function setUp(): void
    {
        $this->changedEntities = new IndexationEntitiesContainer();

        $this->listener = new ReindexParentConfigurableProductListener($this->changedEntities);
    }

    public function testPostPersist()
    {
        $simpleProduct = $this->getSimpleProduct(1);
        $productVariant = $this->getProductVariant(2, 3);

        $this->listener->postPersist($simpleProduct);
        $this->listener->postPersist($productVariant);

        /** @var ProductVariantLink $parent */
        $parent = $productVariant->getParentVariantLinks()->first();

        $this->assertEquals(
            [
                Product::class => [
                    spl_object_hash($parent->getParentProduct()) => $parent->getParentProduct()
                ]
            ],
            $this->changedEntities->getEntities()
        );
    }

    public function testPostUpdate()
    {
        $simpleProduct = $this->getSimpleProduct(1);
        $productVariant = $this->getProductVariant(2, 3);

        $this->listener->postUpdate($simpleProduct);
        $this->listener->postUpdate($productVariant);

        /** @var ProductVariantLink $parent */
        $parent = $productVariant->getParentVariantLinks()->first();

        $this->assertEquals(
            [
                Product::class => [
                    spl_object_hash($parent->getParentProduct()) => $parent->getParentProduct()
                ]
            ],
            $this->changedEntities->getEntities()
        );
    }

    public function testPreRemove()
    {
        $simpleProduct = $this->getSimpleProduct(1);
        $productVariant = $this->getProductVariant(2, 3);

        $this->listener->preRemove($simpleProduct);
        $this->listener->preRemove($productVariant);

        /** @var ProductVariantLink $parent */
        $parent = $productVariant->getParentVariantLinks()->first();

        $this->assertEquals(
            [
                Product::class => [
                    spl_object_hash($parent->getParentProduct()) => $parent->getParentProduct()
                ]
            ],
            $this->changedEntities->getEntities()
        );
    }

    /**
     * @param int $configurableProductId
     * @param int $productVariantId
     * @return Product
     */
    private function getProductVariant($configurableProductId, $productVariantId)
    {
        /** @var Product $productVariant */
        $productVariant = $this->getEntity(
            Product::class,
            ['id' => $productVariantId, 'type' => Product::TYPE_SIMPLE]
        );
        /** @var Product $configurableProduct */
        $configurableProduct = $this->getEntity(
            Product::class,
            ['id' => $configurableProductId, 'type' => Product::TYPE_CONFIGURABLE]
        );
        /** @var ProductVariantLink $variantLink */
        $variantLink = $this->getEntity(ProductVariantLink::class);
        $productVariant->addParentVariantLink($variantLink);
        $configurableProduct->addVariantLink($variantLink);

        return $productVariant;
    }

    /**
     * @param int $simpleProductId
     * @return Product
     */
    private function getSimpleProduct($simpleProductId)
    {
        /** @var Product $simpleProduct */
        $simpleProduct = $this->getEntity(Product::class, ['id' => $simpleProductId, 'type' => Product::TYPE_SIMPLE]);

        return $simpleProduct;
    }
}

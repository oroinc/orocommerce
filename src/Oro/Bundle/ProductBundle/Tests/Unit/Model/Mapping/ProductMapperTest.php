<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapper;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperDataAccessorInterface;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperDataLoaderInterface;

class ProductMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductMapperDataLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dataLoader;

    /** @var ProductMapper */
    private $mapper;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataLoader = $this->createMock(ProductMapperDataLoaderInterface::class);

        $dataAccessor = $this->createMock(ProductMapperDataAccessorInterface::class);
        $dataAccessor->expects(self::any())
            ->method('getItem')
            ->willReturnCallback(function (ArrayCollection $collection, int $itemIndex): \ArrayAccess {
                return $collection[$itemIndex];
            });
        $dataAccessor->expects(self::any())
            ->method('getItemSku')
            ->willReturnCallback(function (\ArrayAccess $item): ?string {
                return $item['productSku'] ?? null;
            });
        $dataAccessor->expects(self::any())
            ->method('getProductSku')
            ->willReturnCallback(function (array $product): string {
                return $product['sku'];
            });
        $dataAccessor->expects(self::any())
            ->method('updateItem')
            ->willReturnCallback(function (\ArrayAccess $item, array $product): void {
                $item['productId'] = $product['id'];
            });

        $this->mapper = new ProductMapper($dataAccessor, $this->dataLoader);
    }

    public function testMapProductsForEmptyCollection(): void
    {
        $collection = new ArrayCollection();

        $this->dataLoader->expects(self::never())
            ->method('loadProducts');

        $this->mapper->mapProducts($collection);

        self::assertCount(0, $collection);
    }

    public function testMapProducts(): void
    {
        $collection = new ArrayCollection();
        $collection->add(new \ArrayObject(['productSku' => 'sku1', 'productItem' => 'items']));
        $collection->add(new \ArrayObject(['productSku' => 'sku2', 'productItem' => 'items']));
        $collection->add(new \ArrayObject(['productSku' => 'sku1', 'productItem' => 'each']));
        $collection->add(new \ArrayObject(['productSku' => 'sku3', 'productItem' => 'items']));

        $this->dataLoader->expects(self::once())
            ->method('loadProducts')
            ->with(['SKU1', 'SKU2', 'SKU3'])
            ->willReturn([
                ['sku' => 'Sku1', 'id' => 1],
                ['sku' => 'Sku2', 'id' => 2]
            ]);

        $this->mapper->mapProducts($collection);

        self::assertSame(
            [
                ['productSku' => 'sku1', 'productItem' => 'items', 'productId' => 1],
                ['productSku' => 'sku2', 'productItem' => 'items', 'productId' => 2],
                ['productSku' => 'sku1', 'productItem' => 'each', 'productId' => 1],
                ['productSku' => 'sku3', 'productItem' => 'items']
            ],
            array_map(
                function (\ArrayObject $item): array {
                    return $item->getArrayCopy();
                },
                $collection->toArray()
            )
        );
    }
}

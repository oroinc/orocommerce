<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue as InventoryStatus;
use Oro\Bundle\InventoryBundle\Tests\Unit\Stubs\ProductStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductAvailabilityProviderTest extends TestCase
{
    /** @var ConfigManager|MockObject */
    private $configManager;

    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    /** @var AclHelper|MockObject */
    private $aclHelper;

    /** @var ProductAvailabilityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->provider = new ProductAvailabilityProvider(
            $this->configManager,
            $this->doctrine,
            $this->aclHelper
        );
        $this->provider->setNotAllowedProductTypes([Product::TYPE_CONFIGURABLE, Product::TYPE_KIT]);
    }

    private function getInventoryStatus(string $id): InventoryStatus
    {
        return new InventoryStatus($id, $id);
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testIsProductAllowedForRFP(
        string $type,
        ?InventoryStatus $inventoryStatus,
        string $status,
        bool $expectedResult
    ): void {
        $product = new ProductStub(1);
        $product->setStatus($status);
        $product->setInventoryStatus($inventoryStatus);
        $product->setType($type);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn(['in_stock']);

        self::assertSame($expectedResult, $this->provider->isProductAllowedForRFP($product));
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testHasProductsAllowedForRFPByProductData(
        string $type,
        ?InventoryStatus $inventoryStatus,
        string $status,
        bool $expectedResult
    ): void {
        $sku = 'sku42';
        $product = new ProductStub(42);
        $product->setStatus($status);
        $product->setInventoryStatus($inventoryStatus);
        $product->setType($type);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($product);

        $repo = $this->createMock(ProductRepository::class);
        $repo->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with($sku)
            ->willReturn($qb);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repo);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        self::assertSame(
            $expectedResult,
            $this->provider->hasProductsAllowedForRFPByProductData([['productSku' => $sku]])
        );
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testHasProductsAllowedForRFP(
        string $type,
        ?InventoryStatus $inventoryStatus,
        string $status,
        bool $expectedResult
    ): void {
        $productId = 42;
        $product = new ProductStub($productId);
        $product->setStatus($status);
        $product->setInventoryStatus($inventoryStatus);
        $product->setType($type);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($product);

        $repo = $this->createMock(ProductRepository::class);
        $repo->expects($this->once())
            ->method('getProductsQueryBuilder')
            ->with([$productId])
            ->willReturn($qb);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repo);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        self::assertSame($expectedResult, $this->provider->hasProductsAllowedForRFP([$productId]));
    }

    public function isAllowedDataProvider(): array
    {
        return [
            [
                'type' => Product::TYPE_CONFIGURABLE,
                'inventoryStatus' => $this->getInventoryStatus('in_stock'),
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => false,
            ],
            [
                'type' => Product::TYPE_SIMPLE,
                'inventoryStatus' => $this->getInventoryStatus('in_stock'),
                'status' => ProductStub::STATUS_DISABLED,
                'expectedResult' => false,
            ],
            [
                'type' => Product::TYPE_SIMPLE,
                'inventoryStatus' => null,
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => false,
            ],
            [
                'type' => Product::TYPE_SIMPLE,
                'inventoryStatus' => $this->getInventoryStatus('in_stock'),
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => true,
            ],
            [
                'type' => Product::TYPE_SIMPLE,
                'inventoryStatus' => $this->getInventoryStatus('out_of_stock'),
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => false,
            ],
        ];
    }
}

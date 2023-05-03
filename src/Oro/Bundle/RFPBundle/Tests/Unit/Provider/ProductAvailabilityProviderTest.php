<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue as InventoryStatus;
use Oro\Bundle\InventoryBundle\Tests\Unit\Stubs\ProductStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ProductAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
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
    }

    private function getInventoryStatus(string $id): InventoryStatus
    {
        return new InventoryStatus($id, $id);
    }

    /**
     * @dataProvider isProductApplicableForRFPDataProvider
     */
    public function testIsProductApplicableForRFP(string $type, bool $expected): void
    {
        $product = new ProductStub(1);
        $product->setType($type);

        $this->assertSame($expected, $this->provider->isProductApplicableForRFP($product));
    }

    public function isProductApplicableForRFPDataProvider(): array
    {
        return [
            'simple product' => [
                'type' => Product::TYPE_SIMPLE,
                'expected' => true,
            ],
            'configurable product' => [
                'type' => Product::TYPE_CONFIGURABLE,
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testIsProductAllowedForRFP(
        ?InventoryStatus $inventoryStatus,
        string $status,
        bool $expectedResult
    ): void {
        $product = new ProductStub(1);
        $product->setStatus($status);
        $product->setInventoryStatus($inventoryStatus);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $this->assertSame($expectedResult, $this->provider->isProductAllowedForRFP($product));
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testHasProductsAllowedForRFP(
        ?InventoryStatus $inventoryStatus,
        string $status,
        bool $expectedResult
    ): void {
        $productId = 42;
        $product = new ProductStub($productId);
        $product->setStatus($status);
        $product->setInventoryStatus($inventoryStatus);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($product);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('from')
            ->with(Product::class, 'p')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('p.id = :id')
            ->willReturnSelf();

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $this->assertSame($expectedResult, $this->provider->hasProductsAllowedForRFP([$productId]));
    }

    public function isAllowedDataProvider(): array
    {
        return [
            [
                'inventoryStatus' => $this->getInventoryStatus('in_stock'),
                'status' => ProductStub::STATUS_DISABLED,
                'expectedResult' => false,
            ],
            [
                'inventoryStatus' => null,
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => false,
            ],
            [
                'inventoryStatus' => $this->getInventoryStatus('in_stock'),
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => true,
            ],
            [
                'inventoryStatus' => $this->getInventoryStatus('out_of_stock'),
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => false,
            ],
        ];
    }
}

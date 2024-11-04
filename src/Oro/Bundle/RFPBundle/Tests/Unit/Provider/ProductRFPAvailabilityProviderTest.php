<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue as InventoryStatus;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\InventoryBundle\Tests\Unit\Stubs\ProductStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductRFPAvailabilityProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;

    private ManagerRegistry|MockObject $doctrine;

    private AclHelper|MockObject $aclHelper;

    private ProductRFPAvailabilityProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->provider = new ProductRFPAvailabilityProvider(
            $this->configManager,
            $this->doctrine,
            $this->aclHelper
        );
    }

    private function getInventoryStatus(string $id): InventoryStatus
    {
        return new InventoryStatus(Product::INVENTORY_STATUS_ENUM_CODE, 'Test', $id);
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

        $this->configManager
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn([ExtendHelper::buildEnumOptionId(Product::INVENTORY_STATUS_ENUM_CODE, 'in_stock')]);

        self::assertSame($expectedResult, $this->provider->isProductAllowedForRFP($product));
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

        $this->configManager
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn([ExtendHelper::buildEnumOptionId(Product::INVENTORY_STATUS_ENUM_CODE, 'in_stock')]);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($product);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('from')
            ->with(Product::class, 'p')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('p.id = :id')
            ->willReturnSelf();

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        self::assertSame($expectedResult, $this->provider->hasProductsAllowedForRFP([$productId]));
    }

    public function isAllowedDataProvider(): array
    {
        return [
            [
                'inventoryStatus' => $this->getInventoryStatus('in_stock'),
                'status' => Product::STATUS_DISABLED,
                'expectedResult' => false,
            ],
            [
                'inventoryStatus' => null,
                'status' => Product::STATUS_ENABLED,
                'expectedResult' => false,
            ],
            [
                'inventoryStatus' => $this->getInventoryStatus('in_stock'),
                'status' => Product::STATUS_ENABLED,
                'expectedResult' => true,
            ],
            [
                'inventoryStatus' => $this->getInventoryStatus('out_of_stock'),
                'status' => Product::STATUS_ENABLED,
                'expectedResult' => false,
            ],
        ];
    }
}

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
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
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
    private ProductManager $productManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->productManager = $this->createMock(ProductManager::class);

        $this->provider = new ProductRFPAvailabilityProvider(
            $this->configManager,
            $this->doctrine,
            $this->aclHelper,
            $this->productManager
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
            ->willReturn([ExtendHelper::buildEnumOptionId(
                Product::INVENTORY_STATUS_ENUM_CODE,
                'in_stock'
            )]);

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

    public function testGetAllowedProductIds(): void
    {
        $productIds = [10, 20, 30];

        $this->provider->setNotAllowedProductTypes([
            Product::TYPE_CONFIGURABLE,
        ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('p.id')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('p.type NOT IN (:notAllowedProductTypes)')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('notAllowedProductTypes', [Product::TYPE_CONFIGURABLE])
            ->willReturnSelf();

        $repository = $this->createMock(ProductRepository::class);
        $repository->expects(self::once())
            ->method('getProductsQueryBuilder')
            ->with($productIds)
            ->willReturn($queryBuilder);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($entityManager);

        $this->productManager->expects(self::once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, ['scope' => 'rfp']);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                ['id' => 10],
                ['id' => 30],
            ]);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        self::assertSame(
            [10, 30],
            $this->provider->getAllowedProductIds($productIds)
        );
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

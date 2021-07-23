<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\InventoryStatusStub;
use Oro\Bundle\InventoryBundle\Tests\Unit\Stubs\ProductStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProviderInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class RequestDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface
     */
    protected $container;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TwigEngine
     */
    protected $templating;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Session
     */
    protected $session;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FlashBagInterface
     */
    protected $flashBag;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductAvailabilityProviderInterface
     */
    protected $productAvailabilityProvider;

    /**
     * @var RequestDataStorageExtension
     */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->extension = new RequestDataStorageExtension(
            $requestStack,
            $this->storage,
            $this->doctrineHelper,
            $this->aclHelper,
            $this->productClass
        );
        $this->extension->setDataClass('Oro\Bundle\RFPBundle\Entity\Request');
        $this->extension->setConfigManager($this->configManager);

        $this->setUpLoggerMock($this->extension);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->templating = $this->getMockBuilder('Symfony\Bundle\TwigBundle\TwigEngine')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Symfony\Contracts\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->flashBag = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productAvailabilityProvider = $this->createMock(ProductAvailabilityProviderInterface::class);

        $this->container->expects($this->any())->method('get')->with('templating')->willReturn($this->templating);

        $this->session->expects($this->any())->method('getFlashBag')->willReturn($this->flashBag);

        $this->extension->setContainer($this->container);
        $this->extension->setTranslator($this->translator);
        $this->extension->setSession($this->session);
        $this->extension->setProductAvailabilityProvider($this->productAvailabilityProvider);

        $this->entity = new RFPRequest();
    }

    public function testBuild()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ],
            ],
        ];
        $this->entity = new RFPRequest();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);
        $product->setStatus(Product::STATUS_ENABLED);
        $inventoryStatus = new TestEnumValue('in_stock', 'In stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->productAvailabilityProvider->expects($this->once())
            ->method('isProductApplicableForRFP')
            ->with($product)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertCount(1, $this->entity->getRequestProducts());
        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->entity->getRequestProducts()->first();

        $this->assertEquals($product, $requestProduct->getProduct());
        $this->assertEquals($product->getSku(), $requestProduct->getProductSku());

        $this->assertCount(1, $requestProduct->getRequestProductItems());
        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        $this->assertEquals($productUnit, $requestProductItem->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $requestProductItem->getProductUnitCode());
        $this->assertEquals($qty, $requestProductItem->getQuantity());
    }

    public function testBuildUnsupportedStatus()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ],
            ],
        ];
        $this->entity = new RFPRequest();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);
        $inventoryStatus = new TestEnumValue('out_of_stock', 'Out of stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->productAvailabilityProvider->expects($this->once())
            ->method('isProductApplicableForRFP')
            ->with($product)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertEmpty($this->entity->getRequestProducts());
    }

    public function testTheIsNoDisabledProductsInRequestProductsAfterExtensionBuild()
    {
        $sku = 'TEST';
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => 3,
                ],
            ],
        ];
        $this->entity = new RFPRequest();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);
        $product->setStatus(Product::STATUS_DISABLED);
        $inventoryStatus = new TestEnumValue('in_stock', 'In stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->productAvailabilityProvider->expects($this->once())
            ->method('isProductApplicableForRFP')
            ->with($product)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_rfp.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertEmpty($this->entity->getRequestProducts());
    }

    public function testBuildUnsupportedProduct()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ],
            ],
        ];
        $this->entity = new RFPRequest();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);
        $inventoryStatus = new TestEnumValue('in_stock', 'In stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->productAvailabilityProvider->expects($this->once())
            ->method('isProductApplicableForRFP')
            ->with($product)
            ->willReturn(false);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertEmpty($this->entity->getRequestProducts());
    }

    public function testBuildWithoutUnit()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ],
            ],
        ];
        $this->entity = new RFPRequest();

        $product = $this->getProductEntity($sku);

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertEmpty($this->entity->getRequestProducts());
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([RequestType::class], RequestDataStorageExtension::getExtendedTypes());
    }

    /**
     * @dataProvider isAllowedRFPDataProvider
     *
     * @param string|InventoryStatusStub $inventoryStatus
     * @param string $status
     * @param bool $expectedResult
     */
    public function testIsAllowedRFP($inventoryStatus, string $status, bool $expectedResult): void
    {
        $sku = 'sku42';
        $product = new ProductStub(42);
        $product->setStatus($status);
        $product->setInventoryStatus($inventoryStatus);

        $this->configManager->expects($this->once())
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

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->productClass)
            ->willReturn($repo);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $this->assertSame($expectedResult, $this->extension->isAllowedRFP([['productSku' => $sku]]));
    }

    public function isAllowedRFPDataProvider(): array
    {
        return [
            [
                'inventoryStatus' => 'in_stock',
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => true,
            ],
            [
                'inventoryStatus' => 'in_stock',
                'status' => ProductStub::STATUS_DISABLED,
                'expectedResult' => false,
            ],
            [
                'inventoryStatus' => '',
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => false,
            ],
            [
                'inventoryStatus' => new InventoryStatusStub('in_stock', 'In Stock'),
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => true,
            ],
            [
                'inventoryStatus' => 'out_of_stock',
                'status' => ProductStub::STATUS_ENABLED,
                'expectedResult' => false,
            ],
        ];
    }

    /**
     * @dataProvider isAllowedRFPDataProvider
     *
     * @param string|InventoryStatusStub $inventoryStatus
     * @param string $status
     * @param bool $expectedResult
     */
    public function testIsAllowedRFPByProductsIds($inventoryStatus, string $status, bool $expectedResult): void
    {
        $productId = 42;
        $product = new ProductStub($productId);
        $product->setStatus($status);
        $product->setInventoryStatus($inventoryStatus);

        $this->configManager->expects($this->once())
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

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->productClass)
            ->willReturn($repo);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $this->assertSame($expectedResult, $this->extension->isAllowedRFPByProductsIds([$productId]));
    }
}

<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;

class RequestDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->extension = new RequestDataStorageExtension(
            $requestStack,
            $this->storage,
            $this->doctrineHelper,
            $this->productClass
        );
        $this->extension->setDataClass('OroB2B\Bundle\RFPBundle\Entity\Request');
        $this->extension->setConfigManager($this->configManager);

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
        $inventoryStatus = new StubEnumValue('in_stock', 'In stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_rfp.frontend_product_visibility')
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
        $inventoryStatus = new StubEnumValue('out_of_stock', 'Out of stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_rfp.frontend_product_visibility')
            ->willReturn(['in_stock']);

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
}

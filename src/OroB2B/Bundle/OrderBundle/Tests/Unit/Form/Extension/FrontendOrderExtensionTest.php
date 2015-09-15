<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Extension\FrontendOrderExtension;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractPostQuickAddTypeExtensionTest;

class FrontendOrderExtensionTest extends AbstractPostQuickAddTypeExtensionTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->extension = new FrontendOrderExtension($this->storage, $this->doctrineHelper, $this->productClass);
        $this->extension->setRequest($this->request);
        $this->extension->setDataClass('OroB2B\Bundle\OrderBundle\Entity\Order');

        $this->entity = new Order();
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(FrontendOrderType::NAME, $this->extension->getExtendedType());
    }

    public function testBuild()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [[ProductRowType::PRODUCT_SKU_FIELD_NAME => $sku, ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => $qty]];
        $order = new Order();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);

        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->extension->buildForm($builder, ['data' => $order]);

        $this->assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();

        $this->assertEquals($product, $lineItem->getProduct());
        $this->assertEquals($product->getSku(), $lineItem->getProductSku());
        $this->assertEquals($productUnit, $lineItem->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $lineItem->getProductUnitCode());
        $this->assertEquals($qty, $lineItem->getQuantity());
    }

    public function testBuildWithoutUnit()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [[ProductRowType::PRODUCT_SKU_FIELD_NAME => $sku, ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => $qty]];
        $order = new Order();

        $product = $this->getProductEntity($sku);

        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->extension->buildForm($builder, ['data' => $order]);

        $this->assertEmpty($order->getLineItems());
    }
}

<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Form\Extension\FrontendRequestExtension;
use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractPostQuickAddTypeExtensionTest;

class FrontendRequestExtensionTest extends AbstractPostQuickAddTypeExtensionTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->extension = new FrontendRequestExtension($this->storage, $this->doctrineHelper, $this->productClass);
        $this->extension->setRequest($this->request);
        $this->extension->setDataClass('OroB2B\Bundle\RFPBundle\Entity\Request');

        $this->entity = new RFPRequest();
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(RequestType::NAME, $this->extension->getExtendedType());
    }

    public function testBuild()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [[ProductRowType::PRODUCT_SKU_FIELD_NAME => $sku, ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => $qty]];
        $request = new RFPRequest();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);

        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->extension->buildForm($builder, ['data' => $request]);

        $this->assertCount(1, $request->getRequestProducts());
        /** @var RequestProduct $requestProduct */
        $requestProduct = $request->getRequestProducts()->first();

        $this->assertEquals($product, $requestProduct->getProduct());
        $this->assertEquals($product->getSku(), $requestProduct->getProductSku());

        $this->assertCount(1, $requestProduct->getRequestProductItems());
        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        $this->assertEquals($productUnit, $requestProductItem->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $requestProductItem->getProductUnitCode());
        $this->assertEquals($qty, $requestProductItem->getQuantity());
    }

    public function testBuildWithoutUnit()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [[ProductRowType::PRODUCT_SKU_FIELD_NAME => $sku, ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => $qty]];
        $request = new RFPRequest();

        $product = $this->getProductEntity($sku);

        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->extension->buildForm($builder, ['data' => $request]);

        $this->assertEmpty($request->getRequestProducts());
    }
}

<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Extension\FrontendOrderExtension;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Model\DataStorageAwareProcessor;
use OroB2B\Bundle\ProductBundle\Model\ProductDataConverter;
use OroB2B\Bundle\ProductBundle\Model\QuickAddProductInformation;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class FrontendOrderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductDataStorage
     */
    protected $storage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductDataConverter
     */
    protected $converter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    /**
     * @var FrontendOrderExtension
     */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->storage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $this->converter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Model\ProductDataConverter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new FrontendOrderExtension($this->storage, $this->converter);
        $this->extension->setRequest($this->request);
    }

    public function testBuildFormNoRequestParameter()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');

        $this->request->expects($this->once())
            ->method('get')
            ->with(DataStorageAwareProcessor::QUICK_ADD_PARAM);

        $this->storage->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($builder, ['data' => new Order()]);
    }

    public function testBuildFormExistingOrder()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $order = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Entity\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $this->request->expects($this->once())
            ->method('get')
            ->with(DataStorageAwareProcessor::QUICK_ADD_PARAM)
            ->will($this->returnValue(1));

        $this->storage->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($builder, ['data' => $order]);
    }

    public function testBuildFormNoData()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');

        $this->request->expects($this->once())
            ->method('get')
            ->with(DataStorageAwareProcessor::QUICK_ADD_PARAM)
            ->will($this->returnValue(1));

        $this->storage->expects($this->once())
            ->method('get');
        $this->storage->expects($this->once())
            ->method('remove');

        $this->converter->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($builder, ['data' => new Order()]);
    }

    public function testBuild()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $data = [[ProductDataConverter::PRODUCT_KEY => 1, ProductDataConverter::QUANTITY_KEY => 3]];
        $order = new Order();
        $product = new Product();
        $product->setSku('TEST');
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($productUnit);
        $product->addUnitPrecision($productUnitPrecision);
        $info = new QuickAddProductInformation();
        $info->setProduct($product)
            ->setQuantity(3);

        $this->request->expects($this->once())
            ->method('get')
            ->with(DataStorageAwareProcessor::QUICK_ADD_PARAM)
            ->will($this->returnValue(1));

        $this->storage->expects($this->once())
            ->method('get')
            ->will($this->returnValue($data));
        $this->storage->expects($this->once())
            ->method('remove');

        $this->converter->expects($this->once())
            ->method('getProductsInfoByStoredData')
            ->with($data)
            ->will($this->returnValue([$info]));

        $this->extension->buildForm($builder, ['data' => $order]);

        $this->assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();

        $this->assertEquals($product, $lineItem->getProduct());
        $this->assertEquals($product->getSku(), $lineItem->getProductSku());
        $this->assertEquals($productUnit, $lineItem->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $lineItem->getProductUnitCode());
        $this->assertEquals($info->getQuantity(), $lineItem->getQuantity());
    }
}

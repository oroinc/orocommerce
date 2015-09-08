<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Extension\FrontendOrderExtension;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Model\DataStorageAwareProcessor;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;

class FrontendOrderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductDataStorage
     */
    protected $storage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    /**
     * @var FrontendOrderExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->storage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productClass = 'stdClass';

        $this->extension = new FrontendOrderExtension($this->storage, $this->registry, $this->productClass);
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

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($builder, ['data' => new Order()]);
    }

    public function testBuild()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $sku = 'TEST';
        $qty = 3;
        $data = [[ProductRowType::PRODUCT_SKU_FIELD_NAME => $sku, ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => $qty]];
        $order = new Order();
        $product = new Product();
        $product->setSku('TEST');
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($productUnit);
        $product->addUnitPrecision($productUnitPrecision);

        $this->request->expects($this->once())
            ->method('get')
            ->with(DataStorageAwareProcessor::QUICK_ADD_PARAM)
            ->will($this->returnValue(1));

        $this->storage->expects($this->once())
            ->method('get')
            ->will($this->returnValue($data));
        $this->storage->expects($this->once())
            ->method('remove');

        $repo = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findOneBySku')
            ->with($sku)
            ->will($this->returnValue($product));
        $em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->productClass)
            ->will($this->returnValue($repo));
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->productClass)
            ->will($this->returnValue($em));

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
}

<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Form\Extension\FrontendRequestExtension;
use OroB2B\Bundle\OrderBundle\Form\Extension\FrontendOrderExtension;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Model\DataStorageAwareProcessor;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;

class FrontendRequestExtensionTest extends \PHPUnit_Framework_TestCase
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

        $this->extension = new FrontendRequestExtension($this->storage, $this->registry, $this->productClass);
        $this->extension->setRequest($this->request);
        $this->extension->setDataClass('OroB2B\Bundle\RFPBundle\Entity\Request');
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

        $this->extension->buildForm($builder, ['data' => new Request()]);
    }

    public function testBuildFormExistingOrder()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $order = $this->getMockBuilder('OroB2B\Bundle\RFPBundle\Entity\Request')
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

        $this->extension->buildForm($builder, ['data' => new RFPRequest()]);
    }

    public function testBuild()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $sku = 'TEST';
        $qty = 3;
        $data = [[ProductRowType::PRODUCT_SKU_FIELD_NAME => $sku, ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => $qty]];
        $request = new RFPRequest();
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
}

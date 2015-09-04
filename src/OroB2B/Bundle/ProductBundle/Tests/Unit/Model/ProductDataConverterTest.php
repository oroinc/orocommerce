<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\ProductDataConverter;
use OroB2B\Bundle\ProductBundle\Model\QuickAddProductInformation;

class ProductDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var ProductDataConverter
     */
    protected $converter;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->dataClass = 'stdClass';
        $this->converter = new ProductDataConverter($this->registry);
        $this->converter->setDataClass($this->dataClass);
    }

    public function testGetProductsInfoByStoredData()
    {
        $data = [[ProductDataConverter::PRODUCT_KEY => 1, ProductDataConverter::QUANTITY_KEY => 3]];

        $product = new Product();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getReference')
            ->with($this->dataClass, 1)
            ->will($this->returnValue($product));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->dataClass)
            ->will($this->returnValue($em));

        $expected = new QuickAddProductInformation();
        $expected->setProduct($product)
            ->setQuantity(3);

        $this->assertEquals([$expected], $this->converter->getProductsInfoByStoredData($data));
    }
}

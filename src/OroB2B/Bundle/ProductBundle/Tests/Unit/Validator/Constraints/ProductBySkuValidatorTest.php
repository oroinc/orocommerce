<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySkuValidator;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySku;

class ProductBySkuValidatorTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'OroB2BProductBundle:Product';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductBySku
     */
    protected $constraint;

    /**
     * @var ProductBySkuValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->constraint = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySku')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new ProductBySkuValidator($this->registry);
        $this->validator->initialize($this->context);
    }

    public function testValidateNoValue()
    {
        $this->registry->expects($this->never())
            ->method('getRepository');

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate('', $this->constraint);
    }

    /**
     * @param string $sku
     * @param Product|null $product
     * @dataProvider validateProvider
     */
    public function testValidate($sku, $product)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ProductRepository */
        $repository = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBySku')
            ->with($sku)
            ->will($this->returnValue($product));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(self::PRODUCT_CLASS)
            ->will($this->returnValue($repository));

        $this->context->expects($product ? $this->never() : $this->once())
            ->method('addViolation')
            ->with($this->constraint->message);

        $this->validator->validate($sku, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            'fail' => ['S12', null],
            'success' => ['S12_1099', new Product()],
        ];
    }
}

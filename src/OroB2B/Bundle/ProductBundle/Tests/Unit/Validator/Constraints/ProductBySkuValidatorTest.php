<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\ProductDataConverter;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySkuValidator;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySku;

class ProductBySkuValidatorTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'OroB2BProductBundle:Product';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductDataConverter
     */
    protected $converter;

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
        $this->converter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Model\ProductDataConverter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->constraint = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySku')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new ProductBySkuValidator($this->converter);
        $this->validator->initialize($this->context);
    }

    protected function tearDown()
    {
        unset($this->registry, $this->context, $this->constraint, $this->validator);
    }

    public function testValidateNoValue()
    {
        $this->converter->expects($this->never())
            ->method($this->anything());

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
        $this->converter->expects($this->once())
            ->method('convertSkuToProduct')
            ->with($sku)
            ->willReturn($product);

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

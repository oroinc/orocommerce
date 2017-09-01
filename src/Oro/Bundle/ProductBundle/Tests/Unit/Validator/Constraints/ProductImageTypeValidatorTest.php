<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageTypeValidator;

class ProductImageTypeValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImageTypeProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $imageTypeProvider;

    /**
     * @var ProductImageTypeValidator
     */
    protected $productImageTypeValidator;

    /**
     * @var Constraint|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $constraint;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    protected function setUp()
    {
        $this->imageTypeProvider = $this->getMockBuilder(ImageTypeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productImageTypeValidator = new ProductImageTypeValidator($this->imageTypeProvider);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->productImageTypeValidator->initialize($this->context);
        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint * */
        $this->constraint = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testValidateShouldIgnore()
    {
        $value = new ProductImageType(null);

        $this->productImageTypeValidator->validate($value, $this->constraint);
    }

    public function testValidateShouldThrowError()
    {
        $value = new ProductImageType('testType');
        $this->imageTypeProvider->expects($this->once())
            ->method('getImageTypes')
            ->willReturn(['otherType' => []]);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->productImageTypeValidator->validate($value, $this->constraint);
    }

    public function testValidateShouldPass()
    {
        $value = new ProductImageType('testType');
        $this->imageTypeProvider->expects($this->once())
            ->method('getImageTypes')
            ->willReturn(['testType' => []]);
        $this->context->expects($this->never())
            ->method('buildViolation')
            ->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->productImageTypeValidator->validate($value, $this->constraint);
    }
}

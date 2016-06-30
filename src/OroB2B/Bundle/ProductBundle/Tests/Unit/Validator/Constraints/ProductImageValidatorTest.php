<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Prophecy\Argument;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;

use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductImage;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductImageValidator;

class ProductImageValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductImageValidator
     */
    protected $validator;

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ProductImage
     */
    protected $constraint;

    public function setUp()
    {
        $this->constraint = new ProductImage();

        $this->context = $this->prophesize('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->validator = new ProductImageValidator();
        $this->validator->initialize($this->context->reveal());
    }

    public function testValidateValidImage()
    {
        $file = new File();
        $file->setFilename('test.jpg');

        $productImage = new StubProductImage();
        $productImage->setImage($file);

        $this->context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validator->validate($productImage, $this->constraint);
    }

    public function testValidateInvalidImage()
    {
        $builder = $this->prophesize('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');

        $this->context
            ->buildViolation($this->constraint->message)
            ->willReturn($builder->reveal());

        $this->validator->validate(new StubProductImage(), $this->constraint);
    }
}

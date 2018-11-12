<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImage;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageCollection;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageValidator;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductImageValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductImageValidator
     */
    protected $productImageValidator;

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ProductImage
     */
    protected $constraint;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function setUp()
    {
        $this->constraint = new ProductImage();

        $this->context = $this->prophesize('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->validator = $this->prophesize('Symfony\Component\Validator\Validator\ValidatorInterface');
        $this->productImageValidator = new ProductImageValidator($this->validator->reveal());
        $this->productImageValidator->initialize($this->context->reveal());
    }

    public function testValidateValidImage()
    {
        $file = new File();
        $file->setFilename('test.jpg');

        $productImage = new StubProductImage();
        $productImage->setImage($file);

        $product = new Product();
        $productImage->setProduct($product);

        $this->validator->validate(
            $product->getImages(),
            new ProductImageCollection()
        )->willReturn(new ArrayCollection());

        $this->context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->productImageValidator->validate($productImage, $this->constraint);
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

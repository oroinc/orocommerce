<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImage;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ProductImageValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductImageValidator */
    protected $productImageValidator;

    /** @var ExecutionContextInterface */
    protected $context;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->productImageValidator = new ProductImageValidator();
        $this->productImageValidator->initialize($this->context);
    }

    public function testValidateValidImage()
    {
        $productImage = new StubProductImage();
        $productImage->setImage((new File())->setFilename('test.jpg'));
        $productImage->setProduct(new Product());

        $this->context->expects(static::never())->method('buildViolation');

        $this->productImageValidator->validate($productImage, new ProductImage());
    }

    public function testValidateInvalidImage()
    {
        $constraint = new ProductImage();
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->context->expects(static::once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $this->productImageValidator->validate(new StubProductImage(), $constraint);
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImage;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImageValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductImageValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new ProductImageValidator();
    }

    public function testValidateValidImage()
    {
        $productImage = new StubProductImage();
        $productImage->setImage((new File())->setFilename('test.jpg'));
        $productImage->setProduct(new Product());

        $constraint = new ProductImage();
        $this->validator->validate($productImage, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateInvalidImage()
    {
        $constraint = new ProductImage();
        $this->validator->validate(new StubProductImage(), $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}

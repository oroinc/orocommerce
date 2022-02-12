<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\VariantField;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributes;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributesValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotEmptyConfigurableAttributesValidatorTest extends ConstraintValidatorTestCase
{
    /** @var VariantFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(VariantFieldProvider::class);
        parent::setUp();
    }

    protected function createValidator(): NotEmptyConfigurableAttributesValidator
    {
        return new NotEmptyConfigurableAttributesValidator($this->provider);
    }

    public function testGetTargets(): void
    {
        $constraint = new NotEmptyConfigurableAttributes();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateUnsupportedClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $constraint = new NotEmptyConfigurableAttributes();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateNotConfigurable(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $this->provider->expects($this->never())
            ->method('getVariantFields');

        $constraint = new NotEmptyConfigurableAttributes();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateConfigurableValid(): void
    {
        $attributeFamily = new AttributeFamily();

        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setAttributeFamily($attributeFamily);

        $this->provider->expects($this->once())
            ->method('getVariantFields')
            ->with($attributeFamily)
            ->willReturn([new VariantField('', '')]);

        $constraint = new NotEmptyConfigurableAttributes();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateConfigurableNotValid(): void
    {
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('default_family');

        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setAttributeFamily($attributeFamily);

        $this->provider->expects($this->once())
            ->method('getVariantFields')
            ->with($attributeFamily)
            ->willReturn([]);

        $constraint = new NotEmptyConfigurableAttributes();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%attributeFamily%', 'default_family')
            ->assertRaised();
    }

    public function testValidateConfigurableWithoutAttributeFamily(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);

        $this->provider->expects($this->never())
            ->method('getVariantFields');

        $constraint = new NotEmptyConfigurableAttributes();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }
}

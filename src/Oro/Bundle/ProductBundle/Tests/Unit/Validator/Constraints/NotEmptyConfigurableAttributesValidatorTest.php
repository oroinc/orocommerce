<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\VariantField;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributes;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributesValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NotEmptyConfigurableAttributesValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var NotEmptyConfigurableAttributesValidator */
    private $validator;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var VariantFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->provider = $this->createMock(VariantFieldProvider::class);

        $this->validator = new NotEmptyConfigurableAttributesValidator($this->provider);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->validator, $this->context, $this->provider);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given
     */
    public function testValidateUnsupportedClass()
    {
        $this->validator->validate(new \stdClass(), new NotEmptyConfigurableAttributes());
    }

    public function testValidateNotConfigurable()
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $this->provider->expects($this->never())->method('getVariantFields');
        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($product, new NotEmptyConfigurableAttributes());
    }

    public function testValidateConfigurableValid()
    {
        $attributeFamily = new AttributeFamily();

        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setAttributeFamily($attributeFamily);

        $this->provider
            ->expects($this->once())
            ->method('getVariantFields')
            ->with($attributeFamily)
            ->willReturn([new VariantField('', '')]);

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($product, new NotEmptyConfigurableAttributes());
    }

    public function testValidateConfigurableNotValid()
    {
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('default_family');

        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setAttributeFamily($attributeFamily);

        $this->provider
            ->expects($this->once())
            ->method('getVariantFields')
            ->with($attributeFamily)
            ->willReturn([]);

        $constraint = new NotEmptyConfigurableAttributes();

        $this->context
            ->expects($this->once())
            ->method('addViolation')
            ->with($constraint->message, ['%attributeFamily%' => 'default_family']);

        $this->validator->validate($product, new NotEmptyConfigurableAttributes());
    }
}

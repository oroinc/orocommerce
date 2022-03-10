<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantField;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantFieldValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductVariantFieldValidatorTest extends ConstraintValidatorTestCase
{
    /** @var CustomFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $customFieldProvider;

    private array $variantFields = [
        'field_first',
        'field_second'
    ];

    private array $incorrectCustomVariantFields = [
        'field_first' => [
            'name' => 'field_first',
            'type' => 'string',
            'label' => 'field_first'
        ],
        'field_third' => [
            'name' => 'field_third',
            'type' => 'string',
            'label' => 'field_third'
        ]
    ];

    private array $correctCustomVariantFields = [
        'field_first' => [
            'name' => 'field_first',
            'type' => 'string',
            'label' => 'field_first'
        ],
        'field_second' => [
            'name' => 'field_second',
            'type' => 'string',
            'label' => 'field_second'
        ]
    ];

    protected function setUp(): void
    {
        $this->customFieldProvider = $this->createMock(CustomFieldProvider::class);
        parent::setUp();
    }

    protected function createValidator(): ProductVariantFieldValidator
    {
        return new ProductVariantFieldValidator($this->customFieldProvider);
    }

    public function testGetTargets()
    {
        $constraint = new ProductVariantField();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testDoesNothingIfEmptyProductCustomFields()
    {
        $product = new Product();
        $productClass = ClassUtils::getClass($product);

        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($productClass)
            ->willReturn([]);

        $constraint = new ProductVariantField();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testAddViolationIfProductDoesNotHaveFields()
    {
        $product = $this->prepareProductWithVariantFields($this->variantFields);
        $productClass = ClassUtils::getClass($product);

        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($productClass)
            ->willReturn([]);

        $constraint = new ProductVariantField();
        $this->validator->validate($product, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ field }}', 'field_first')
            ->buildNextViolation($constraint->message)
            ->setParameter('{{ field }}', 'field_second')
            ->assertRaised();
    }

    public function testDoesNotAddViolationIfVariantFieldsExistInCustomFields()
    {
        $product = $this->prepareProductWithVariantFields($this->variantFields);
        $productClass = ClassUtils::getClass($product);

        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($productClass)
            ->willReturn($this->correctCustomVariantFields);

        $constraint = new ProductVariantField();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testAddViolationIfVariantFieldDoesNotExistInCustomField()
    {
        $product = $this->prepareProductWithVariantFields($this->variantFields);

        $productClass = ClassUtils::getClass($product);

        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($productClass)
            ->willReturn($this->incorrectCustomVariantFields);

        $constraint = new ProductVariantField();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ field }}', 'field_second')
            ->assertRaised();
    }

    private function prepareProductWithVariantFields(array $variantFields): Product
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields($variantFields);

        return $product;
    }
}

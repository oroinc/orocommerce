<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemProductAvailable;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemProductAvailableValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductKitItemLineItemProductAvailableValidatorTest extends ConstraintValidatorTestCase
{
    private EntityStateChecker|MockObject $entityStateChecker;

    private ValidatorInterface|MockObject $validatorComponent;

    private ContextualValidatorInterface|MockObject $innerContextualValidator;

    private ProductKitItemLineItemProductAvailable $productKitItemLineItemProductAvailableConstraint;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityStateChecker = $this->createMock(EntityStateChecker::class);
        $this->validatorComponent = $this->createMock(ValidatorInterface::class);

        $this->productKitItemLineItemProductAvailableConstraint = new ProductKitItemLineItemProductAvailable(
            ['availabilityValidationGroups' => ['sample_group'], 'ifChanged' => ['product']]
        );

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): ProductKitItemLineItemProductAvailableValidator
    {
        return new ProductKitItemLineItemProductAvailableValidator($this->entityStateChecker);
    }

    #[\Override]
    protected function createContext()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0);

        $context = new ExecutionContext($this->validatorComponent, $this->root, $translator);
        $context->setGroup($this->group);
        $context->setNode($this->value, $this->object, $this->metadata, $this->propertyPath);
        $context->setConstraint($this->constraint);

        $this->innerContextualValidator = $this->createMock(ContextualValidatorInterface::class);
        $this->validatorComponent
            ->method('inContext')
            ->with($context)
            ->willReturn($this->innerContextualValidator);

        return $context;
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductKitItemLineItemProductAvailable::class)
        );

        $this->validator->validate([], $constraint);
    }

    public function testValidateWhenNullValue(): void
    {
        $this->validator->validate(null, $this->productKitItemLineItemProductAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenInvalidValue(): void
    {
        $value = new \stdClass();
        $this->expectExceptionObject(new UnexpectedValueException($value, Product::class));

        $this->validator->validate($value, $this->productKitItemLineItemProductAvailableConstraint);
    }

    public function testValidateWhenInvalidObject(): void
    {
        $product = new Product();
        $object = new \stdClass();
        $this->expectExceptionObject(new UnexpectedValueException($object, ProductKitItemLineItemInterface::class));

        $this->setObject($object);
        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);
    }

    public function testValidateWhenNoKitItem(): void
    {
        $product = new Product();
        $kitItemLineItem = new ProductKitItemLineItemStub(42);
        $this->setObject($kitItemLineItem);

        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNotNewNotChanged(): void
    {
        $product = new Product();
        $kitItem = new ProductKitItem();
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($kitItemLineItem, ['product'])
            ->willReturn(false);

        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsNewAndNoProductKitItemProduct(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItem = new ProductKitItem();
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(true);

        $this->entityStateChecker
            ->expects(self::never())
            ->method('isChangedEntity');

        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this
            ->buildViolation($this->productKitItemLineItemProductAvailableConstraint->message)
            ->setParameter('{{ product_sku }}', '"' . $product->getSku() . '"')
            ->setCode(ProductKitItemLineItemProductAvailable::PRODUCT_NOT_ALLOWED)
            ->setCause($product)
            ->assertRaised();
    }

    public function testValidateWhenIsNewAndNotAllowed(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(true);

        $this->entityStateChecker
            ->expects(self::never())
            ->method('isChangedEntity');

        $innerContextualValidator = $this->innerContextualValidator;
        $innerContextualValidator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $kitItemProduct,
                null,
                $this->productKitItemLineItemProductAvailableConstraint->availabilityValidationGroups
            )
            ->willReturnCallback(function () use (&$innerContextualValidator) {
                $this->context->addViolation('sample violation');

                return $innerContextualValidator;
            });

        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this
            ->buildViolation('sample violation')
            ->assertRaised();
    }

    public function testValidateWhenIsNewAndAllowed(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(true);

        $this->entityStateChecker
            ->expects(self::never())
            ->method('isChangedEntity');

        $this->innerContextualValidator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $kitItemProduct,
                null,
                $this->productKitItemLineItemProductAvailableConstraint->availabilityValidationGroups
            );

        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsNewAndNoAvailabilityValidationGroups(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(true);

        $this->entityStateChecker
            ->expects(self::never())
            ->method('isChangedEntity');

        $this->innerContextualValidator
            ->expects(self::never())
            ->method('validate');

        $this->productKitItemLineItemProductAvailableConstraint->availabilityValidationGroups = [];
        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsChangedAndNoProductKitItemProduct(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItem = new ProductKitItem();
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($kitItemLineItem, ['product'])
            ->willReturn(true);

        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this
            ->buildViolation($this->productKitItemLineItemProductAvailableConstraint->message)
            ->setParameter('{{ product_sku }}', '"' . $product->getSku() . '"')
            ->setCode(ProductKitItemLineItemProductAvailable::PRODUCT_NOT_ALLOWED)
            ->setCause($product)
            ->assertRaised();
    }

    public function testValidateWhenIsChangedAndNotAllowed(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($kitItemLineItem, ['product'])
            ->willReturn(true);

        $innerContextualValidator = $this->innerContextualValidator;
        $innerContextualValidator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $kitItemProduct,
                null,
                $this->productKitItemLineItemProductAvailableConstraint->availabilityValidationGroups
            )
            ->willReturnCallback(function () use (&$innerContextualValidator) {
                $this->context->addViolation('sample violation');

                return $innerContextualValidator;
            });

        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this
            ->buildViolation('sample violation')
            ->assertRaised();
    }

    public function testValidateWhenIsChangedAndAllowed(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($kitItemLineItem, ['product'])
            ->willReturn(true);

        $this->innerContextualValidator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $kitItemProduct,
                null,
                $this->productKitItemLineItemProductAvailableConstraint->availabilityValidationGroups
            );

        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsChangedAndNoAvailabilityValidationGroups(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($kitItemLineItem, ['product'])
            ->willReturn(true);

        $this->innerContextualValidator
            ->expects(self::never())
            ->method('validate');

        $this->productKitItemLineItemProductAvailableConstraint->availabilityValidationGroups = [];
        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNotIfChangedAndNotAllowed(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::never())
            ->method(self::anything());

        $innerContextualValidator = $this->innerContextualValidator;
        $innerContextualValidator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $kitItemProduct,
                null,
                $this->productKitItemLineItemProductAvailableConstraint->availabilityValidationGroups
            )
            ->willReturnCallback(function () use (&$innerContextualValidator) {
                $this->context->addViolation('sample violation');

                return $innerContextualValidator;
            });

        $this->productKitItemLineItemProductAvailableConstraint->ifChanged = [];

        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this
            ->buildViolation('sample violation')
            ->assertRaised();
    }

    public function testValidateWhenNotIfChangedAndAllowed(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::never())
            ->method(self::anything());

        $this->innerContextualValidator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $kitItemProduct,
                null,
                $this->productKitItemLineItemProductAvailableConstraint->availabilityValidationGroups
            );

        $this->productKitItemLineItemProductAvailableConstraint->ifChanged = [];
        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNotIfChangedAndNoAvailabilityValidationGroups(): void
    {
        $product = (new Product())->setSku('SKU1');
        $kitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::never())
            ->method(self::anything());

        $this->innerContextualValidator
            ->expects(self::never())
            ->method('validate');

        $this->productKitItemLineItemProductAvailableConstraint->ifChanged = [];
        $this->productKitItemLineItemProductAvailableConstraint->availabilityValidationGroups = [];
        $this->validator->validate($product, $this->productKitItemLineItemProductAvailableConstraint);

        $this->assertNoViolation();
    }
}

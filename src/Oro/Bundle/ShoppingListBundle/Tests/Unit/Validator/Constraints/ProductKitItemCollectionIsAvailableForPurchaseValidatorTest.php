<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemCollectionIsAvailableForPurchase;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemCollectionIsAvailableForPurchaseValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductKitItemCollectionIsAvailableForPurchaseValidatorTest extends ConstraintValidatorTestCase
{
    private LocalizationHelper $localizationHelper;

    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        parent::setUp();
    }

    protected function createValidator(): ProductKitItemCollectionIsAvailableForPurchaseValidator
    {
        return new ProductKitItemCollectionIsAvailableForPurchaseValidator($this->localizationHelper);
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductKitItemCollectionIsAvailableForPurchase::class)
        );

        $this->validator->validate([], $constraint);
    }

    public function testValidateWhenInvalidValue(): void
    {
        $constraint = new ProductKitItemCollectionIsAvailableForPurchase();
        $value = 'not_array';
        $this->expectExceptionObject(new UnexpectedValueException($value, 'iterable'));

        $this->validator->validate($value, $constraint);
    }

    public function testValidateWhenEmptyCollection(): void
    {
        $constraint = new ProductKitItemCollectionIsAvailableForPurchase();
        $value = [];

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->noAvailableKitItemsMessage)
            ->setCode(ProductKitItemCollectionIsAvailableForPurchase::NO_AVAILABLE_KIT_ITEMS_ERROR)
            ->assertRaised();
    }

    public function testValidateWhenNotEmptyCollectionAndNoViolations(): void
    {
        $constraint = new ProductKitItemCollectionIsAvailableForPurchase();
        $kitItem1 = new ProductKitItem();
        $kitItem2 = new ProductKitItem();
        $value = [$kitItem1, $kitItem2];

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNotEmptyCollectionAndHasViolationsButIsOptional(): void
    {
        $constraint = new ProductKitItemCollectionIsAvailableForPurchase();
        $kitItem1 = (new ProductKitItem())
            ->setOptional(true);
        $kitItem2 = (new ProductKitItem())
            ->setOptional(true);
        $value = [$kitItem1, $kitItem2];

        (\Closure::bind(function () {
            $this->expectedViolations[] = new ConstraintViolationList(
                [$this->createMock(ConstraintViolationInterface::class)]
            );
        }, $this, ConstraintValidatorTestCase::class))();

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNotEmptyCollectionAndHasViolationsAndIsNotOptional(): void
    {
        $constraint = new ProductKitItemCollectionIsAvailableForPurchase();
        $kitItemLabel = 'Sample Kit Item';
        $kitItem1 = (new ProductKitItem())
            ->addLabel((new ProductKitItemLabel())->setString($kitItemLabel))
            ->setOptional(false);
        $kitItem2 = (new ProductKitItem())
            ->setOptional(true);
        $productKit = (new Product())
            ->setSku('KIT1')
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $value = [$kitItem1, $kitItem2];

        $violation1 = new ConstraintViolation('sample_error1', null, [], $kitItem1, null, $kitItem1);
        $violation2 = new ConstraintViolation('sample_error2', null, [], $kitItem1, null, $kitItem1);
        $violationList = new ConstraintViolationList([$violation1, $violation2]);
        (\Closure::bind(function () use ($violationList) {
            $this->expectedViolations[] = $violationList;
        }, $this, ConstraintValidatorTestCase::class))();

        $this->localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (iterable $values) => $values[0]->getString());

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->requiredKitItemNotAvailableMessage)
            ->setParameter('{{ product_kit_item_label }}', '"' . $kitItemLabel . '"')
            ->setParameter('{{ product_kit_sku }}', '"' . $productKit->getSku() . '"')
            ->setParameter('{{ reason }}', '"' . $violation1->getMessage() . '", "' . $violation2->getMessage() . '"')
            ->setCode(ProductKitItemCollectionIsAvailableForPurchase::REQUIRED_KIT_ITEM_NOT_AVAILABLE_ERROR)
            ->setCause($violationList)
            ->assertRaised();
    }
}

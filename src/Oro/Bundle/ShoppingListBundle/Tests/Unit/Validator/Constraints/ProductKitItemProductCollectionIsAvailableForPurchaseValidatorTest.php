<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemProductCollectionIsAvailableForPurchase;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemProductCollectionIsAvailableForPurchaseValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductKitItemProductCollectionIsAvailableForPurchaseValidatorTest extends ConstraintValidatorTestCase
{
    private LocalizationHelper $localizationHelper;

    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        parent::setUp();
    }

    protected function createValidator(): ProductKitItemProductCollectionIsAvailableForPurchaseValidator
    {
        return new ProductKitItemProductCollectionIsAvailableForPurchaseValidator($this->localizationHelper);
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductKitItemProductCollectionIsAvailableForPurchase::class)
        );

        $this->validator->validate([], $constraint);
    }

    public function testValidateWhenInvalidValue(): void
    {
        $constraint = new ProductKitItemProductCollectionIsAvailableForPurchase();
        $value = 'not_array';
        $this->expectExceptionObject(new UnexpectedValueException($value, 'iterable'));

        $this->validator->validate($value, $constraint);
    }

    public function testValidateWhenEmptyCollection(): void
    {
        $constraint = new ProductKitItemProductCollectionIsAvailableForPurchase();
        $value = [];

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->emptyMessage)
            ->setCode(ProductKitItemProductCollectionIsAvailableForPurchase::NO_AVAILABLE_PRODUCTS_ERROR)
            ->assertRaised();
    }

    public function testValidateWhenNotEmptyCollectionAndNoViolations(): void
    {
        $constraint = new ProductKitItemProductCollectionIsAvailableForPurchase();
        $kitItemProduct1 = new ProductKitItemProduct();
        $kitItemProduct2 = new ProductKitItemProduct();
        $value = [$kitItemProduct1, $kitItemProduct2];

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNotEmptyCollectionAndHasViolations(): void
    {
        $constraint = new ProductKitItemProductCollectionIsAvailableForPurchase();
        $kitItemLabel = 'Sample Kit Item';
        $kitItemProduct1 = new ProductKitItemProduct();
        $kitItemProduct2 = new ProductKitItemProduct();
        (new ProductKitItem())
            ->addLabel((new ProductKitItemLabel())->setString($kitItemLabel))
            ->addKitItemProduct($kitItemProduct1)
            ->addKitItemProduct($kitItemProduct2);
        $value = [$kitItemProduct1, $kitItemProduct2];

        $violation1 = new ConstraintViolation('sample_error1', null, [], $kitItemProduct1, null, $kitItemProduct1);
        $violationList1 = new ConstraintViolationList([$violation1]);
        $violation2 = new ConstraintViolation('sample_error2', null, [], $kitItemProduct2, null, $kitItemProduct2);
        $violationList2 = new ConstraintViolationList([$violation2]);
        (\Closure::bind(function () use ($violationList1, $violationList2) {
            $this->expectedViolations[] = $violationList1;
            $this->expectedViolations[] = $violationList2;
        }, $this, ConstraintValidatorTestCase::class))();

        $this->localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (iterable $values) => $values[0]->getString());

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ product_kit_item_label }}', '"' . $kitItemLabel . '"')
            ->setCode(ProductKitItemProductCollectionIsAvailableForPurchase::NO_AVAILABLE_PRODUCTS_ERROR)
            ->assertRaised();
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemProductCollectionIsAvailableForPurchase) {
            throw new UnexpectedTypeException(
                $constraint,
                ProductKitItemProductCollectionIsAvailableForPurchase::class
            );
        }

        if (!is_iterable($value)) {
            throw new UnexpectedValueException($value, 'iterable');
        }

        $validator = $this->context->getValidator();
        $productsCount = $unavailableProductsCount = 0;
        $kitItemLabel = null;
        foreach ($value as $kitItemProduct) {
            $productsCount++;
            $constraintViolations = $validator->validate(
                $kitItemProduct,
                null,
                ['product_kit_item_product_is_available_for_purchase']
            );
            if ($constraintViolations->count() > 0) {
                $unavailableProductsCount++;

                if ($kitItemLabel === null) {
                    $kitItemLabel = (string)$this->localizationHelper
                        ->getLocalizedValue($kitItemProduct->getKitItem()?->getLabels());
                }
            }
        }

        if ($productsCount === $unavailableProductsCount) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ product_kit_item_label }}', $this->formatValue($kitItemLabel))
                ->setCode(ProductKitItemProductCollectionIsAvailableForPurchase::NO_AVAILABLE_PRODUCTS_ERROR)
                ->addViolation();
        }
    }
}

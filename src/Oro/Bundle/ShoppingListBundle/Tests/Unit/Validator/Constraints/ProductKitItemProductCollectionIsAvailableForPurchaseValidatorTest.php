<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemProductCollectionIsAvailableForPurchase;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemProductCollectionIsAvailableForPurchaseValidator;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductKitItemProductCollectionIsAvailableForPurchaseValidatorTest extends ConstraintValidatorTestCase
{
    private LocalizationHelper&MockObject $localizationHelper;
    private TranslationMessageSanitizerInterface&MockObject $translationMessageSanitizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->translationMessageSanitizer = $this->createMock(TranslationMessageSanitizerInterface::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): ProductKitItemProductCollectionIsAvailableForPurchaseValidator
    {
        return new ProductKitItemProductCollectionIsAvailableForPurchaseValidator(
            $this->localizationHelper,
            $this->translationMessageSanitizer
        );
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

        $this->localizationHelper->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (iterable $values) => $values[0]->getString());

        $this->translationMessageSanitizer->expects(self::once())
            ->method('sanitizeMessage')
            ->with($kitItemLabel)
            ->willReturn($kitItemLabel . '_sanitized');

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ product_kit_item_label }}', '"' . $kitItemLabel . '_sanitized"')
            ->setCode(ProductKitItemProductCollectionIsAvailableForPurchase::NO_AVAILABLE_PRODUCTS_ERROR)
            ->assertRaised();
    }
}

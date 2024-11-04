<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemCollectionIsAvailableForPurchase;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemCollectionIsAvailableForPurchaseValidator;
use Oro\Bundle\TranslationBundle\Translation\TranslationMessageSanitizerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductKitItemCollectionIsAvailableForPurchaseValidatorTest extends ConstraintValidatorTestCase
{
    private TranslationMessageSanitizerInterface|MockObject $translationMessageSanitizer;

    private ValidatorInterface|MockObject $validatorComponent;

    private array $validationGroups = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->translationMessageSanitizer = $this->createMock(TranslationMessageSanitizerInterface::class);
        $this->validatorComponent = $this->createMock(ValidatorInterface::class);

        $this->validationGroups = [new GroupSequence(['Default', 'product_kit_item_is_available_for_purchase'])];

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): ProductKitItemCollectionIsAvailableForPurchaseValidator
    {
        return new ProductKitItemCollectionIsAvailableForPurchaseValidator($this->translationMessageSanitizer);
    }

    #[\Override]
    protected function createContext()
    {
        $context = parent::createContext();
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0);
        $contextualValidator = $context->getValidator()->inContext($context);

        $context = new ExecutionContext($this->validatorComponent, $this->root, $translator);
        $context->setGroup($this->group);
        $context->setNode($this->value, $this->object, $this->metadata, $this->propertyPath);
        $context->setConstraint($this->constraint);

        $this->validatorComponent
            ->method('inContext')
            ->with($context)
            ->willReturn($contextualValidator);

        return $context;
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

        $this->validatorComponent
            ->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive(
                [$kitItem1, null, $this->validationGroups],
                [$kitItem2, null, $this->validationGroups]
            )
            ->willReturn(new ConstraintViolationList());

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

        $this->validatorComponent
            ->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive(
                [$kitItem1, null, $this->validationGroups],
                [$kitItem2, null, $this->validationGroups]
            )
            ->willReturnOnConsecutiveCalls(
                new ConstraintViolationList(
                    [new ConstraintViolation('sample_error1', null, [], $kitItem1, null, $kitItem1)]
                ),
                new ConstraintViolationList()
            );

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
        $kitItem3 = (new ProductKitItem())
            ->setOptional(false);
        $productKit = (new Product())
            ->setSku('KIT1')
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2)
            ->addKitItem($kitItem3);
        $value = [$kitItem1, $kitItem2, $kitItem3];

        $violation1 = new ConstraintViolation('sample_error1', null, [], $kitItem1, null, $kitItem1);
        $violation2 = new ConstraintViolation('sample_error2', null, [], $kitItem1, null, $kitItem1);
        $violationList = new ConstraintViolationList([$violation1, $violation2]);

        $this->validatorComponent
            ->expects(self::exactly(3))
            ->method('validate')
            ->withConsecutive(
                [$kitItem1, null, $this->validationGroups],
                [$kitItem2, null, $this->validationGroups]
            )
            ->willReturnOnConsecutiveCalls(
                new ConstraintViolationList([$violation1, $violation2]),
                new ConstraintViolationList(),
                new ConstraintViolationList(),
            );

        $this->translationMessageSanitizer
            ->expects(self::exactly(3))
            ->method('sanitizeMessage')
            ->willReturnCallback(static fn (string $message) => $message . '_sanitized');

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->requiredKitItemNotAvailableMessage)
            ->setParameter('{{ product_kit_sku }}', '"' . $productKit->getSku() . '_sanitized"')
            ->setParameter(
                '{{ reason }}',
                '"' . $violation1->getMessage() . '_sanitized", "' . $violation2->getMessage() . '_sanitized"'
            )
            ->setCode(ProductKitItemCollectionIsAvailableForPurchase::REQUIRED_KIT_ITEM_NOT_AVAILABLE_ERROR)
            ->setCause($violationList)
            ->assertRaised();
    }
}

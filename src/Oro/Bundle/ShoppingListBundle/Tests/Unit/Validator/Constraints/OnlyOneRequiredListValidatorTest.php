<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\OnlyOneRequiredList;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\OnlyOneRequiredListValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class OnlyOneRequiredListValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): OnlyOneRequiredListValidator
    {
        return new OnlyOneRequiredListValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new OnlyOneRequiredList();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateWrongEntity(): void
    {
        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage(\sprintf(
            'Expected argument of type "%s", "%s" given',
            LineItem::class,
            ShoppingList::class
        ));

        $constraint = new OnlyOneRequiredList();
        $this->validator->validate(new ShoppingList(), $constraint);
    }

    public function testValidateWrongConstraint(): void
    {
        self::expectException(UnexpectedTypeException::class);
        self::expectExceptionMessage(\sprintf(
            'Expected argument of type "%s", "%s" given',
            OnlyOneRequiredList::class,
            MatrixCollectionColumn::class
        ));

        $constraint = new MatrixCollectionColumn();
        $this->validator->validate(new LineItem(), $constraint);
    }

    public function testValidateLineItemWithoutShoppingList(): void
    {
        $constraint = new OnlyOneRequiredList();
        $this->validator->validate(new LineItem(), $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateLineItemWithTwoRelationsToShoppingList(): void
    {
        $lineItem = new LineItem();
        $lineItem->setShoppingList(new ShoppingList());
        ReflectionUtil::setPropertyValue($lineItem, 'savedForLaterList', new ShoppingList());
        $constraint = new OnlyOneRequiredList();

        $this->validator->validate($lineItem, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidate(): void
    {
        $lineItem = new LineItem();
        $lineItem->setShoppingList(new ShoppingList());
        $constraint = new OnlyOneRequiredList();

        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }
}

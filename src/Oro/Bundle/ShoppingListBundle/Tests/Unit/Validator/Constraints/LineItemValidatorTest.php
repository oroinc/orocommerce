<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItem as LineItemConstraint;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class LineItemValidatorTest extends ConstraintValidatorTestCase
{
    private ManagerRegistry&MockObject $doctrine;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): LineItemValidator
    {
        return new LineItemValidator($this->doctrine);
    }

    public function testValidateNoDuplicate(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $lineItem = $this->createMock(LineItem::class);
        $lineItem->expects(self::any())
            ->method('getAssociatedList')
            ->willReturn($shoppingList);

        $repository = $this->createMock(LineItemRepository::class);
        $repository->expects(self::once())
            ->method('findDuplicateInShoppingList')
            ->with($lineItem, $shoppingList)
            ->willReturn(null);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $constraint = new LineItemConstraint();
        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateHasDuplicate(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $lineItem = $this->createMock(LineItem::class);
        $lineItem->expects(self::any())
            ->method('getAssociatedList')
            ->willReturn($shoppingList);

        $repository = $this->createMock(LineItemRepository::class);
        $repository->expects(self::once())
            ->method('findDuplicateInShoppingList')
            ->with($lineItem, $shoppingList)
            ->willReturn($this->createMock(LineItem::class));

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $constraint = new LineItemConstraint();
        $this->validator->validate($lineItem, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}

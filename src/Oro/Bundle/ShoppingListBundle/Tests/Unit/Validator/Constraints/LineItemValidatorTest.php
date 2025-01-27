<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItem as LineItemConstraint;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LineItemValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

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
        $lineItem->expects($this->any())
            ->method('getShoppingList')
            ->willReturn($shoppingList);

        $repository = $this->createMock(LineItemRepository::class);
        $repository->expects($this->once())
            ->method('findDuplicateInShoppingList')
            ->with($lineItem, $shoppingList)
            ->willReturn(null);

        $this->doctrine->expects($this->once())
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
        $lineItem->expects($this->any())
            ->method('getShoppingList')
            ->willReturn($shoppingList);

        $repository = $this->createMock(LineItemRepository::class);
        $repository->expects($this->once())
            ->method('findDuplicateInShoppingList')
            ->with($lineItem, $shoppingList)
            ->willReturn($this->createMock(LineItem::class));

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $constraint = new LineItemConstraint();
        $this->validator->validate($lineItem, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}

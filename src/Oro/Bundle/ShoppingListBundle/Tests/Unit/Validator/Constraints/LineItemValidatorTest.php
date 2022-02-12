<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItem as LineItemConstraint;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LineItemValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var LineItemRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var LineItem|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItem;

    /** @var ShoppingList|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingList;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(LineItemRepository::class);
        $this->lineItem = $this->createMock(LineItem::class);
        $this->shoppingList = $this->createMock(ShoppingList::class);

        $this->lineItem->expects($this->any())
            ->method('getShoppingList')
            ->willReturn($this->shoppingList);

        parent::setUp();
    }

    protected function createValidator(): LineItemValidator
    {
        return new LineItemValidator($this->managerRegistry);
    }

    public function testGetTargets(): void
    {
        $constraint = new LineItemConstraint();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateNoDuplicate(): void
    {
        $this->repository->expects($this->once())
            ->method('findDuplicateInShoppingList')
            ->with($this->lineItem, $this->shoppingList)
            ->willReturn(null);

        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($this->repository);

        $constraint = new LineItemConstraint();
        $this->validator->validate($this->lineItem, $constraint);

        $this->assertNoViolation();
    }

    public function testValidate(): void
    {
        $this->repository->expects($this->once())
            ->method('findDuplicateInShoppingList')
            ->with($this->lineItem, $this->shoppingList)
            ->willReturn($this->createMock(LineItem::class));

        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($this->repository);

        $constraint = new LineItemConstraint();
        $this->validator->validate($this->lineItem, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}

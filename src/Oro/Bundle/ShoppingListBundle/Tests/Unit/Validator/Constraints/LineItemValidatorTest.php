<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItem as LineItemConstraint;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LineItemValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var LineItemRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    private $context;

    /** @var LineItem|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItem;

    /** @var ShoppingList|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingList;

    /** @var LineItemConstraint|\PHPUnit\Framework\MockObject\MockObject */
    private $constraint;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(LineItemRepository::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->lineItem = $this->createMock(LineItem::class);
        $this->constraint = $this->createMock(LineItemConstraint::class);
        $this->shoppingList = $this->createMock(ShoppingList::class);

        $this->lineItem
            ->expects($this->once())
            ->method('getShoppingList')
            ->willReturn($this->shoppingList);
    }

    public function testValidateNoDuplicate(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findDuplicateInShoppingList')
            ->with($this->lineItem, $this->shoppingList)
            ->willReturn(null);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($this->repository);

        $this->context
            ->expects($this->never())
            ->method('addViolation');

        $validator = new LineItemValidator($this->managerRegistry);
        $validator->initialize($this->context);
        $validator->validate($this->lineItem, $this->constraint);
    }

    public function testValidate(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findDuplicateInShoppingList')
            ->with($this->lineItem, $this->shoppingList)
            ->willReturn($this->createMock(LineItem::class));

        $this->managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($this->repository);

        $this->context
            ->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->message);

        $validator = new LineItemValidator($this->managerRegistry);
        $validator->initialize($this->context);
        $validator->validate($this->lineItem, $this->constraint);
    }
}

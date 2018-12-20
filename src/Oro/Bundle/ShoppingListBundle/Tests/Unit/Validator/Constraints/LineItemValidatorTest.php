<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItem as LineItemConstraint;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LineItemValidatorTest extends \PHPUnit\Framework\TestCase
{
    const LINE_ITEM_SHORTCUT = 'OroShoppingListBundle:LineItem';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Registry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LineItemRepository
     */
    protected $repository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LineItem
     */
    protected $lineItem;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LineItemConstraint
     */
    protected $constraint;

    protected function setUp()
    {
        $this->registry = $this->createMock(Registry::class);
        $this->repository = $this->createMock(LineItemRepository::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->lineItem = $this->createMock(LineItem::class);
        $this->constraint = $this->createMock(LineItemConstraint::class);
    }

    public function testValidateNoDuplicate()
    {
        $this->repository->expects($this->once())
            ->method('findDuplicate')
            ->with($this->lineItem)
            ->will($this->returnValue(null));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($this->repository));

        $this->context->expects($this->never())
            ->method('addViolation');

        $validator = new LineItemValidator($this->registry);
        $validator->initialize($this->context);
        $validator->validate($this->lineItem, $this->constraint);
    }

    public function testValidate()
    {
        $this->repository->expects($this->once())
            ->method('findDuplicate')
            ->with($this->lineItem)
            ->will($this->returnValue(true));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($this->repository));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->message);

        $validator = new LineItemValidator($this->registry);
        $validator->initialize($this->context);
        $validator->validate($this->lineItem, $this->constraint);
    }
}

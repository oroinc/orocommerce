<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Validator\Constraints\OrderUniqueEntityValidator;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OrderUniqueEntityValidatorTest extends TestCase
{
    private ConstraintValidatorInterface&MockObject $inner;
    private DraftSessionOrmFilterManager&MockObject $manager;
    private Constraint&MockObject $constraint;
    private OrderUniqueEntityValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->inner = $this->createMock(ConstraintValidatorInterface::class);
        $this->manager = $this->createMock(DraftSessionOrmFilterManager::class);
        $this->constraint = $this->createMock(Constraint::class);
        $this->validator = new OrderUniqueEntityValidator($this->inner, $this->manager);
    }

    /**
     * @dataProvider provideDraftAwareFilterStates
     */
    public function testDraftAwareEntityFilter(
        object $value,
        ?bool $isEnabled,
        int $isEnabledCount,
        int $enableCount,
        int $disableCount
    ): void {
        $this->manager->expects(self::exactly($isEnabledCount))
            ->method('isEnabled')
            ->willReturn($isEnabled ?? false);

        $this->manager->expects(self::exactly($enableCount))
            ->method('enable');

        $this->manager->expects(self::exactly($disableCount))
            ->method('disable');

        $this->inner->expects(self::once())
            ->method('validate')
            ->with($value, $this->constraint);

        $this->validator->validate($value, $this->constraint);
    }

    public static function provideDraftAwareFilterStates(): iterable
    {
        yield 'non-Order value' => [new \stdClass(), null, 0, 0, 0];
        yield 'Order, filter already enabled' => [new Order(), true, 1, 0, 0];
        yield 'Order, filter disabled is enabled and restored' => [new Order(), false, 1, 1, 1];
    }

    public function testInitializePropagatesToInner(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);

        $this->inner->expects(self::once())->method('initialize')->with($context);

        $this->validator->initialize($context);
    }
}

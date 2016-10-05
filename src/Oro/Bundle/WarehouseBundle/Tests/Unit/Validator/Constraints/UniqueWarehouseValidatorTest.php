<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\SystemConfig\WarehouseConfig;
use Oro\Bundle\WarehouseBundle\Validator\Constraints\UniqueWarehouse;
use Oro\Bundle\WarehouseBundle\Validator\Constraints\UniqueWarehouseValidator;
use Oro\Component\Testing\Unit\EntityTrait;

class UniqueWarehouseValidatorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var  UniqueWarehouse */
    protected $constraint;

    /** @var UniqueWarehouseValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new UniqueWarehouse();
        $this->validator = new UniqueWarehouseValidator();
    }

    public function testValidateOnValid()
    {
        /** @var Warehouse $warehouse1 */
        $warehouse1 = $this->getEntity(Warehouse::class, ['id' => 1]);
        /** @var Warehouse $warehouse2 */
        $warehouse2 = $this->getEntity(Warehouse::class, ['id' => 2]);
        $testData = [
            new WarehouseConfig($warehouse1, 100),
            new WarehouseConfig($warehouse2, 200)
        ];

        $this->validator->initialize($this->getContextMock());
        $this->validator->validate($testData, $this->constraint);
    }

    public function testValidationOnInvalid()
    {
        /** @var Warehouse $warehouse1 */
        $warehouse1 = $this->getEntity(Warehouse::class, ['id' => 1]);
        /** @var Warehouse $warehouse2 */
        $warehouse2 = $this->getEntity(Warehouse::class, ['id' => 2]);
        $testData = [
            new WarehouseConfig($warehouse1, 100),
            new WarehouseConfig($warehouse2, 200),
            new WarehouseConfig($warehouse1, 100),
        ];
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('atPath')
            ->with('[2].warehouse')
            ->willReturn($builder);

        $context = $this->getContextMock();
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($this->equalTo($this->constraint->getMessage()), [])
            ->will($this->returnValue($builder));

        $this->validator->initialize($context);

        $this->validator->validate($testData, $this->constraint);
    }

    /**
     * @return ExecutionContext|\PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context
     */
    protected function getContextMock()
    {
        return $this
            ->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['buildViolation'])
            ->getMock();
    }

    /**
     * @return ConstraintViolationBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBuilderMock()
    {
        return $this
            ->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addViolation', 'atPath'])
            ->getMock();
    }
}

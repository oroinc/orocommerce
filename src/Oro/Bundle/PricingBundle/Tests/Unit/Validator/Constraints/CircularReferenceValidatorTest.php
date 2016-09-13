<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Expression\BinaryNode;
use Oro\Bundle\PricingBundle\Expression\NameNode;
use Oro\Bundle\PricingBundle\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Oro\Bundle\PricingBundle\Expression\ValueNode;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Validator\Constraints\CircularReference;
use Oro\Bundle\PricingBundle\Validator\Constraints\CircularReferenceValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class CircularReferenceValidatorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    /**
     * @var ExpressionParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $expressionParser;

    /**
     * @var ExpressionPreprocessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $preprocessor;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var CircularReferenceValidator
     */
    protected $validator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->expressionParser = $this->getMockBuilder(ExpressionParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->preprocessor = $this->getMockBuilder(ExpressionPreprocessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder(RegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new CircularReferenceValidator(
            $this->expressionParser,
            $this->preprocessor,
            $this->doctrine
        );
    }

    public function testValidateNameNodeValid()
    {
        /** @var CircularReference|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMockBuilder(CircularReference::class)
            ->disableOriginalConstructor()
            ->getMock();
        $constraint->fields = ['productAssignmentRule'];

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method($this->anything());

        $this->validator->initialize($context);

        $value = 'priceList[2].productAssignmentRule';
        $referenceValue = 'priceList[3].productAssignmentRule';
        $finalValue = 123;

        $this->expressionParser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($value),
                $this->equalTo($referenceValue),
                $this->equalTo($finalValue)
            )
            ->willReturnOnConsecutiveCalls(
                $this->getNameNode(PriceList::class, 2, 'productAssignmentRule'),
                $this->getNameNode(PriceList::class, 3, 'productAssignmentRule'),
                $this->getValueNode()
            );

        $entity = $this->getEntity(PriceList::class, ['id' => 1, 'productAssignmentRule' => $value]);
        $referenceEntity = $this->getEntity(PriceList::class, ['id' => 2, 'productAssignmentRule' => $referenceValue]);
        $finalEntity = $this->getEntity(PriceList::class, ['id' => 3, 'productAssignmentRule' => $finalValue]);

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $referenceEntity,
                $finalEntity
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $this->validator->validate($entity, $constraint);
    }

    public function testValidateNameNodeInvalid()
    {
        /** @var CircularReference|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMockBuilder(CircularReference::class)
            ->disableOriginalConstructor()
            ->getMock();
        $constraint->fields = ['productAssignmentRule'];

        $violation = $this->getMock(ConstraintViolationBuilderInterface::class);
        $violation->expects($this->once())
            ->method('atPath')
            ->with('productAssignmentRule')
            ->will($this->returnSelf());

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->will($this->returnValue($violation));

        $this->validator->initialize($context);

        $value = 'priceList[2].productAssignmentRule';
        $referenceValue = 'priceList[3].productAssignmentRule';
        $finalValue = 'priceList[1].productAssignmentRule';

        $this->expressionParser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($value),
                $this->equalTo($referenceValue),
                $this->equalTo($finalValue)
            )
            ->willReturnOnConsecutiveCalls(
                $this->getNameNode(PriceList::class, 2, 'productAssignmentRule'),
                $this->getNameNode(PriceList::class, 3, 'productAssignmentRule'),
                $this->getNameNode(PriceList::class, 1, 'productAssignmentRule')
            );

        $entity = $this->getEntity(PriceList::class, ['id' => 1, 'productAssignmentRule' => $value]);
        $referenceEntity = $this->getEntity(PriceList::class, ['id' => 2, 'productAssignmentRule' => $referenceValue]);
        $finalEntity = $this->getEntity(PriceList::class, ['id' => 3, 'productAssignmentRule' => $finalValue]);

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $referenceEntity,
                $finalEntity,
                $entity
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);
        $this->validator->validate($entity, $constraint);
    }

    public function testValidateCrossReferenceValid()
    {
        /** @var CircularReference|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMockBuilder(CircularReference::class)
            ->disableOriginalConstructor()
            ->getMock();
        $constraint->fields = ['productAssignmentRule'];

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method($this->anything());

        $this->validator->initialize($context);

        $value = 'priceList[2].name';
        $referenceValue = 123;

        $this->expressionParser->expects($this->exactly(2))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($value),
                $this->equalTo($referenceValue)
            )
            ->willReturnOnConsecutiveCalls(
                $this->getNameNode(PriceList::class, 2, 'name'),
                $this->getValueNode()
            );

        $entity = $this->getEntity(PriceList::class, ['id' => 1, 'productAssignmentRule' => $value]);
        $referenceEntity = $this->getEntity(PriceList::class, ['id' => 2, 'name' => $referenceValue]);

        $this->entityRepository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($referenceEntity);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $this->validator->validate($entity, $constraint);
    }

    public function testValidateCrossReferenceInvalid()
    {
        /** @var CircularReference|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMockBuilder(CircularReference::class)
            ->disableOriginalConstructor()
            ->getMock();
        $constraint->fields = ['productAssignmentRule'];

        $violation = $this->getMock(ConstraintViolationBuilderInterface::class);
        $violation->expects($this->once())
            ->method('atPath')
            ->with('productAssignmentRule')
            ->will($this->returnSelf());

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->will($this->returnValue($violation));

        $this->validator->initialize($context);

        $value = 'priceList[2].name';
        $referenceValue = 'priceList[1].productAssignmentRule';

        $this->expressionParser->expects($this->exactly(2))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($value),
                $this->equalTo($referenceValue)
            )
            ->willReturnOnConsecutiveCalls(
                $this->getNameNode(PriceList::class, 2, 'name'),
                $this->getNameNode(PriceList::class, 1, 'productAssignmentRule')
            );

        $entity = $this->getEntity(PriceList::class, ['id' => 1, 'productAssignmentRule' => $value]);
        $referenceEntity = $this->getEntity(PriceList::class, ['id' => 2, 'name' => $referenceValue]);

        $this->entityRepository->expects($this->exactly(1))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2)
            )
            ->willReturnOnConsecutiveCalls(
                $referenceEntity
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);
        $this->validator->validate($entity, $constraint);
    }

    public function testValidateBinaryNodeValid()
    {
        /** @var CircularReference|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMockBuilder(CircularReference::class)
            ->disableOriginalConstructor()
            ->getMock();
        $constraint->fields = ['productAssignmentRule'];

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method($this->anything());

        $this->validator->initialize($context);

        $value = 'priceList[2].productAssignmentRule and priceList[3].productAssignmentRule';
        $leftValue = 123;
        $rightValue = 345;

        $leftNode = $this->getNameNode(PriceList::class, 2, 'productAssignmentRule');
        $rightNode = $this->getNameNode(PriceList::class, 3, 'productAssignmentRule');

        $this->expressionParser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($value),
                $this->equalTo($leftValue),
                $this->equalTo($rightValue)
            )
            ->willReturnOnConsecutiveCalls(
                $this->getBinaryNode($leftNode, $rightNode),
                $this->getValueNode(),
                $this->getValueNode()
            );

        $entity = $this->getEntity(PriceList::class, ['id' => 1, 'productAssignmentRule' => $value]);
        $leftEntity = $this->getEntity(PriceList::class, ['id' => 2, 'productAssignmentRule' => $leftValue]);
        $rightEntity = $this->getEntity(PriceList::class, ['id' => 3, 'productAssignmentRule' => $rightValue]);

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $leftEntity,
                $rightEntity
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $this->validator->validate($entity, $constraint);
    }

    public function testValidateBinaryNodeInvalid()
    {
        /** @var CircularReference|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMockBuilder(CircularReference::class)
            ->disableOriginalConstructor()
            ->getMock();
        $constraint->fields = ['productAssignmentRule'];

        $violation = $this->getMock(ConstraintViolationBuilderInterface::class);
        $violation->expects($this->once())
            ->method('atPath')
            ->with('productAssignmentRule')
            ->will($this->returnSelf());

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->will($this->returnValue($violation));

        $this->validator->initialize($context);

        $value = 'priceList[2].productAssignmentRule and priceList[3].productAssignmentRule';
        $leftValue = 123;
        $rightValue = 'priceList[1].productAssignmentRule';

        $leftNode = $this->getNameNode(PriceList::class, 2, 'productAssignmentRule');
        $rightNode = $this->getNameNode(PriceList::class, 3, 'productAssignmentRule');

        $this->expressionParser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($value),
                $this->equalTo($leftValue),
                $this->equalTo($rightValue)
            )
            ->willReturnOnConsecutiveCalls(
                $this->getBinaryNode($leftNode, $rightNode),
                $this->getValueNode(),
                $this->getNameNode(PriceList::class, 1, 'productAssignmentRule')
            );

        $entity = $this->getEntity(PriceList::class, ['id' => 1, 'productAssignmentRule' => $value]);
        $leftEntity = $this->getEntity(PriceList::class, ['id' => 2, 'productAssignmentRule' => $leftValue]);
        $rightEntity = $this->getEntity(PriceList::class, ['id' => 3, 'productAssignmentRule' => $rightValue]);

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $leftEntity,
                $rightEntity
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);
        $this->validator->validate($entity, $constraint);
    }

    public function testNewObject()
    {
        /** @var CircularReference|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMockBuilder(CircularReference::class)
            ->disableOriginalConstructor()
            ->getMock();
        $constraint->fields = ['productAssignmentRule'];

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method($this->anything());

        $this->validator->initialize($context);

        $value = 'priceList[2].productAssignmentRule';

        $this->expressionParser->expects($this->never())
            ->method($this->anything());

        $entity = $this->getEntity(PriceList::class, ['productAssignmentRule' => $value]);

        $this->entityRepository->expects($this->never())
            ->method($this->anything());

        $this->entityManager->expects($this->never())
            ->method($this->anything());

        $this->doctrine->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($entity, $constraint);
    }

    protected function getNameNode($container, $containerId, $field)
    {
        $node = $this->getMockBuilder(NameNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        $node->expects($this->any())
            ->method('getContainer')
            ->willReturn($container);
        $node->expects($this->once())
            ->method('getContainerId')
            ->willReturn($containerId);
        $node->expects($this->any())
            ->method('getField')
            ->willReturn($field);
        $node->expects($this->any())
            ->method('getNodes')
            ->willReturn([$node]);

        return $node;
    }

    protected function getValueNode()
    {
        $node = $this->getMockBuilder(ValueNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        $node->expects($this->once())
            ->method('getNodes')
            ->willReturn([$node]);

        return $node;
    }

    protected function getCircularNode($containerId, $field)
    {
        $node = $this->getMockBuilder(NameNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        $node->expects($this->once())
            ->method('getContainerId')
            ->willReturn($containerId);
        $node->expects($this->any())
            ->method('getField')
            ->willReturn($field);
        $node->expects($this->once())
            ->method('getNodes')
            ->willReturn([$node]);

        return $node;
    }

    protected function getBinaryNode($leftNode, $rightNode)
    {
        $node = $this->getMockBuilder(BinaryNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        $node->expects($this->once())
            ->method('getNodes')
            ->willReturn([
                $node, $leftNode, $rightNode
            ]);

        return $node;
    }
}

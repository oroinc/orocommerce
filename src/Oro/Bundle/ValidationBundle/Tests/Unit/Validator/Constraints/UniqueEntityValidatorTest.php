<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Tools\SchemaTool;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UniqueEntityValidator;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="unique_entity_validator_test")
 */
class UniqueEntityValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UniqueEntityValidator
     */
    protected $validator;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected $em;

    public function setUp()
    {
        $this->em = DoctrineTestHelper::createTestEntityManager();
        $this->registry = $this->createRegistryMock();

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema(
            [
                $this->em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity'),
                $this->em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity'),
                $this->em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\CompositeIntIdEntity'),
                $this->em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity'),
            ]
        );

        $this->validator = new UniqueEntityValidator($this->registry);
    }

    protected function createContext($constraint)
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $contextualValidator = $this->createMock(ContextualValidatorInterface::class);

        $context = new ExecutionContext(
            $validator,
            'root',
            $translator
        );

        $context->setGroup('MyGroup');
        $context->setNode('InvalidValue', null, null, 'property.path');
        $context->setConstraint($constraint);

        $validator->expects($this->any())
            ->method('inContext')
            ->with($context)
            ->will($this->returnValue($contextualValidator));

        return $context;
    }

    protected function createRegistryMock()
    {
        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue($this->em));

        return $registry;
    }

    /**
     * @param $message
     * @param $root
     * @param $propertyPath
     * @param $invalidValue
     * @param $code
     * @param $constraint
     * @param array $parameters
     *
     * @return ConstraintViolation
     */
    private function createViolation(
        $message,
        $root,
        $propertyPath,
        $invalidValue,
        $code,
        $constraint,
        $parameters = []
    ) {
        return new ConstraintViolation(
            null,
            $message,
            $parameters,
            $root,
            $propertyPath,
            $invalidValue,
            null,
            $code,
            $constraint,
            null
        );
    }

    protected function validate($constraint, $context)
    {
        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Foo');

        $this->validator->validate($entity1, $constraint);

        $this->assertSame(
            0,
            $violationsCount = count($context->getViolations()),
            sprintf('0 violation expected. Got %u.', $violationsCount)
        );

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->initialize($context);
        $this->validator->validate($entity1, $constraint);

        $this->assertSame(
            0,
            $violationsCount = count($context->getViolations()),
            sprintf('0 violation expected. Got %u.', $violationsCount)
        );

        $this->validator->validate($entity2, $constraint);
    }

    public function testValidateViolationsAtEntityLevel()
    {
        $constraint = new UniqueEntity(
            [
                'message' => 'myMessage',
                'fields' => ['name'],
                'em' => 'foo',
                'buildViolationAtEntityLevel' => true,
            ]
        );

        $context = $this->createContext($constraint);

        $this->validate($constraint, $context);

        $expectedViolation = $this->createViolation(
            'myMessage',
            'root',
            'property.path',
            'InvalidValue',
            UniqueEntity::NOT_UNIQUE_ERROR,
            $constraint,
            [
                'unique_key' => 'name',
            ]
        );

        $this->assertCount(
            1,
            $context->getViolations(),
            sprintf(
                '1 violation expected. Got %u.',
                count($context->getViolations())
            )
        );

        $violation = $context->getViolations()->get(0);

        $this->assertEquals($expectedViolation, $violation);
    }

    public function testValidateViolationsAtPropertyPathLevel()
    {
        $constraint = new UniqueEntity(
            [
                'message' => 'myMessage',
                'fields' => ['name'],
                'em' => 'foo',
                'buildViolationAtEntityLevel' => false,
            ]
        );

        $context = $this->createContext($constraint);

        $this->validate($constraint, $context);

        $expectedViolation = $this->createViolation(
            'myMessage',
            'root',
            'property.path.name',
            'Foo',
            UniqueEntity::NOT_UNIQUE_ERROR,
            $constraint
        );

        $this->assertCount(
            1,
            $context->getViolations(),
            sprintf(
                '1 violation expected. Got %u.',
                count($context->getViolations())
            )
        );

        $violation = $context->getViolations()->get(0);

        $this->assertEquals($expectedViolation, $violation);
    }
}

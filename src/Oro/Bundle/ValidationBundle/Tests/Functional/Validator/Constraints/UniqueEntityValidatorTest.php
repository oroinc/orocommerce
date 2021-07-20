<?php

namespace Oro\Bundle\ValidationBundle\Tests\Functional\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @dbIsolationPerTest
 */
class UniqueEntityValidatorTest extends WebTestCase
{
    /**
     * @var UniqueEntityValidator
     */
    private $validator;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ObjectManager
     */
    private $em;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->registry = $this->getContainer()->get('doctrine');
        $this->em = $this->registry->getManager();

        $this->validator = new UniqueEntityValidator($this->registry);
    }

    /**
     * @param Constraint $constraint
     * @return ExecutionContext
     */
    protected function createContext(Constraint $constraint)
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

    protected function validate(Constraint $constraint, ExecutionContextInterface $context)
    {
        $entity1 = new Product();
        $entity1->setName('Foo');

        $entity2 = new Product();
        $entity2->setName('Foo');

        $this->validator->initialize($context);
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
                'em' => 'default',
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
                'em' => 'default',
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

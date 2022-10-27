<?php

namespace Oro\Bundle\ValidationBundle\Tests\Functional\Validator\Constraints;

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
    /** @var UniqueEntityValidator */
    private $validator;

    /** @var ObjectManager */
    private $em;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $doctrine = $this->getContainer()->get('doctrine');
        $this->em = $doctrine->getManager();

        $this->validator = new UniqueEntityValidator($doctrine);
    }

    private function createContext(Constraint $constraint): ExecutionContext
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $contextualValidator = $this->createMock(ContextualValidatorInterface::class);

        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

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
            ->willReturn($contextualValidator);

        return $context;
    }

    private function createViolation(
        string $message,
        string $root,
        string $propertyPath,
        string $invalidValue,
        string $code,
        UniqueEntity $constraint,
        array $parameters = []
    ): ConstraintViolation {
        return new ConstraintViolation(
            $message,
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

    private function validate(Constraint $constraint, ExecutionContextInterface $context): void
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

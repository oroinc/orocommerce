<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UniqueEntityValidator;
use Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UniqueEntityValidatorTest extends \Symfony\Bridge\Doctrine\Tests\Validator\Constraints\UniqueEntityValidatorTest
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
     */
    protected $context;

    /**
     * @var UniqueEntityValidator
     */
    protected $validator;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function createValidator()
    {
        return new UniqueEntityValidator($this->registry);
    }

    protected function createContext()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $contextualValidator = $this->createMock(ContextualValidatorInterface::class);

        $context = new ExecutionContext(
            $validator,
            $this->root,
            $translator
        );

        $context->setGroup($this->group);
        $context->setNode($this->value, $this->object, $this->metadata, $this->propertyPath);
        $context->setConstraint($this->constraint);

        $validator->expects($this->any())
            ->method('inContext')
            ->with($context)
            ->will($this->returnValue($contextualValidator));

        return $context;
    }

    protected function createRegistryMock(ObjectManager $em = null)
    {
        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo(self::EM_NAME))
            ->will($this->returnValue($em));

        return $registry;
    }

    public function testValidateViolationsAtEntityLevel()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
            'buildViolationAtEntityLevel' => true
        ));

        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Foo');

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->validator->validate($entity2, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('unique_key', 'name')
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }

    public function testValidateViolationsAtPropertyPathLevel()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
            'buildViolationAtEntityLevel' => false
        ));

        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Foo');

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->validator->validate($entity2, $constraint);

        $this->buildViolation('myMessage')
            ->atPath('property.path.name')
            ->setInvalidValue('Foo')
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }
}

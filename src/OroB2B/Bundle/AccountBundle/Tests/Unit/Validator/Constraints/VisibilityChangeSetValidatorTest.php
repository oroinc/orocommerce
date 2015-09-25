<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Validator\Constraints;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\AccountBundle\Validator\Constraints\VisibilityChangeSet;
use OroB2B\Bundle\AccountBundle\Validator\Constraints\VisibilityChangeSetValidator;

class VisibilityChangeSetValidatorTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Account';

    /** @var  VisibilityChangeSet */
    protected $constraint;

    /** @var  ArrayCollection */
    protected $value;

    /** @var  VisibilityChangeSetValidator */
    protected $visibilityChangeSetValidator;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->constraint = new VisibilityChangeSet(['entityClass' => self::ENTITY_CLASS]);
        $this->value = new ArrayCollection();
        $this->visibilityChangeSetValidator = new VisibilityChangeSetValidator();
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->visibilityChangeSetValidator->initialize($this->context);

    }

    public function testValidateEmptyArrayCollection()
    {
        $this->context->expects($this->once())->method('addViolation')->with($this->constraint->invalidFormatMessage);
        $this->visibilityChangeSetValidator->validate($this->value, $this->constraint);
    }

    public function testValidateInvalidKeys()
    {
        $wrongData = ['wrong' => 'data'];
        $this->value->offsetSet(1, $wrongData);
        $this->context->expects($this->once())->method('addViolation')->with($this->constraint->invalidFormatMessage);
        $this->visibilityChangeSetValidator->validate($this->value, $this->constraint);
    }

    public function testValidateAnotherEntity()
    {
        $data['data']['visibility'] = 'visible';
        $data['entity'] = new  ArrayCollection();
        $this->value->offsetSet('1', $data);
        $this->context->expects($this->once())->method('addViolation')->with($this->constraint->invalidDataMessage);
        $this->visibilityChangeSetValidator->validate($this->value, $this->constraint);
    }

    public function testValidData()
    {
        $data['data']['visibility'] = 'visible';
        $data['entity'] = new  Account();
        $this->value->offsetSet('1', $data);
        $this->context->expects($this->never())->method('addViolation');
    }
}

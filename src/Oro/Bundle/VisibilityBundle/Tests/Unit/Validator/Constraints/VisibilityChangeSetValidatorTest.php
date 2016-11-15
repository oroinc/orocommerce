<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\VisibilityBundle\Validator\Constraints\VisibilityChangeSet;
use Oro\Bundle\VisibilityBundle\Validator\Constraints\VisibilityChangeSetValidator;

class VisibilityChangeSetValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  VisibilityChangeSet */
    protected $constraint;

    /** @var ArrayCollection */
    protected $value;

    /** @var VisibilityChangeSetValidator */
    protected $visibilityChangeSetValidator;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->constraint = new VisibilityChangeSet(['entityClass' => Account::class]);
        $this->value = new ArrayCollection();
        $this->visibilityChangeSetValidator = new VisibilityChangeSetValidator();
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->visibilityChangeSetValidator->initialize($this->context);
    }

    public function testValidateEmptyArrayCollection()
    {
        $this->context->expects($this->never())->method('addViolation');
        $this->visibilityChangeSetValidator->validate($this->value, $this->constraint);
    }

    public function testValidateAnotherEntity()
    {
        $data['data']['visibility'] = 'visible';
        $data['entity'] = new ArrayCollection();
        $this->value->offsetSet('1', $data);
        $this->context->expects($this->once())->method('addViolation')->with($this->constraint->invalidDataMessage);
        $this->visibilityChangeSetValidator->validate($this->value, $this->constraint);
    }

    public function testValidData()
    {
        $data['data']['visibility'] = 'visible';
        $data['entity'] = new Account();
        $this->value->offsetSet('1', $data);
        $this->context->expects($this->never())->method('addViolation');
        $this->visibilityChangeSetValidator->validate($data, $this->constraint);
    }

    public function testValidateNotCollection()
    {
        $this->context->expects($this->never())->method('addViolation');
        $this->visibilityChangeSetValidator->validate(new \stdClass(), $this->constraint);
    }
}

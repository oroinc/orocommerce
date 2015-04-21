<?php

namespace OroB2B\Bundle\UserBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\UserBundle\Form\Type\RegistrationFormType;

class RegistrationFormTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistrationFormType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new RegistrationFormType('OroB2B\Bundle\UserAdminBundle\Entity\User');
    }

    public function testBuildFormTest()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'firstName',
                'text',
                ['label' => 'orob2b_user.form.first_name.label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'lastName',
                'text',
                ['label' => 'orob2b_user.form.last_name.label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'email',
                'email',
                ['label' => 'orob2b_user.form.email.label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'plainPassword',
                'repeated',
                [
                    'type' => 'password',
                    'first_options' => ['label' => 'orob2b_user.form.password.label'],
                    'second_options' => ['label' => 'orob2b_user.form.password_confirmation.label'],
                    'invalid_message' => 'orob2b_user.message.password_mismatch',
                ]
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testGetName()
    {
        $this->assertEquals(RegistrationFormType::NAME, $this->type->getName());
    }
}

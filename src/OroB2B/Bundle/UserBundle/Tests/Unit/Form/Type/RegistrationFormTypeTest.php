<?php

namespace OroB2B\Bundle\UserBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use OroB2B\Bundle\UserBundle\Entity\User;
use OroB2B\Bundle\UserBundle\Form\Type\RegistrationFormType;

class RegistrationFormTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RegistrationFormType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new RegistrationFormType('OroB2B\Bundle\UserBundle\Entity\User');
    }

    /**
     * @dataProvider submitDataProvider
     * @param mixed $submittedData
     */
    public function testSubmit($submittedData)
    {
        $user = new User();
        $form = $this->factory->create($this->formType, $user);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($user, $form->getData());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'submit' => [
                'submittedData' => [
                    'firstName' => 'First Name',
                    'lastName' => 'Last Name',
                    'email' => 'test@example.com',
                    'plainPassword' => 'password',
                ],
            ]
        ];
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

        $this->formType->buildForm($builder, []);
    }

    public function testGetName()
    {
        $this->assertEquals(RegistrationFormType::NAME, $this->formType->getName());
    }
}

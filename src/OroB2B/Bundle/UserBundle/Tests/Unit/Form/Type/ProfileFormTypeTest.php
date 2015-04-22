<?php

namespace OroB2B\Bundle\UserBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

use OroB2B\Bundle\UserBundle\Entity\User;
use OroB2B\Bundle\UserBundle\Form\Type\ProfileFormType;

class ProfileFormTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProfileFormType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        /** @var \Symfony\Component\Validator\ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $validator */
        $validator = $this->getMock('\Symfony\Component\Validator\ValidatorInterface');
        $validator->expects($this->any())
            ->method('validate')
            ->will($this->returnValue(new ConstraintViolationList()));

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();

        $this->formType = new ProfileFormType('OroB2B\Bundle\UserBundle\Entity\User');
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
                ],
            ]
        ];
    }

    public function testBuildUserFormTest()
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

        $class = new \ReflectionClass($this->formType);
        $method = $class->getMethod('buildUserForm');
        $method->setAccessible(true);

        $method->invokeArgs($this->formType, [$builder, []]);
    }

    public function testGetName()
    {
        $this->assertEquals(ProfileFormType::NAME, $this->formType->getName());
    }
}

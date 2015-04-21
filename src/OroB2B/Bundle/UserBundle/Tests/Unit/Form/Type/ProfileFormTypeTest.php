<?php

namespace OroB2B\Bundle\UserBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\UserBundle\Form\Type\ProfileFormType;

class ProfileFormTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProfileFormType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new ProfileFormType('OroB2B\Bundle\UserAdminBundle\Entity\User');
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

        $class = new \ReflectionClass($this->type);
        $method = $class->getMethod('buildUserForm');
        $method->setAccessible(true);

        $method->invokeArgs($this->type, [$builder, []]);
    }

    public function testGetName()
    {
        $this->assertEquals(ProfileFormType::NAME, $this->type->getName());
    }
}

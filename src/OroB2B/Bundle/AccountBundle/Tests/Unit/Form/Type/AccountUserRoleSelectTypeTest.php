<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleSelectType;

class AccountUserRoleSelectTypeTest extends FormIntegrationTestCase
{
    /** @var  AccountUserRoleSelectType */
    protected $formType;

    /** @var string */
    protected $roleClass;

    public function setUp()
    {
        parent::setUp();
        $this->formType = new AccountUserRoleSelectType();
        $this->roleClass = 'RoleClass';
        $this->formType->setRoleClass($this->roleClass);
    }

    public function testConfigureOptions()
    {
        /** @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'class' => $this->roleClass,
                'multiple' => true,
                'expanded' => true,
                'required' => true
            ]);
        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals($this->formType->getName(), AccountUserRoleSelectType::NAME);
    }

    public function testGetParent()
    {
        $this->assertEquals($this->formType->getParent(), 'entity');
    }
}

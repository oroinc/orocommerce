<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserMultiSelectType;

class AccountUserMultiSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var AccountUserMultiSelectType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new AccountUserMultiSelectType();
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'autocomplete_alias' => 'orob2b_account_account_user',
                    'configs' => [
                        'multiple' => true,
                        'component' => 'autocomplete-accountuser',
                        'placeholder' => 'orob2b.account.accountuser.form.choose',
                    ],
                    'attr' => [
                        'class' => 'account-accountuser-multiselect',
                    ],
                ]
            );

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(UserMultiSelectType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(AccountUserMultiSelectType::NAME, $this->formType->getName());
    }
}

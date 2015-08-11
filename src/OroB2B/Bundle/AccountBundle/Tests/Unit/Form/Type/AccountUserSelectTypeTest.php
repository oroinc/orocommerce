<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;

class AccountUserSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AccountUserSelectType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new AccountUserSelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(AccountUserSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->formType->getParent());
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals('orob2b_account_account_user', $options['autocomplete_alias']);
                    $this->assertEquals('orob2b_account_account_user_create', $options['create_form_route']);
                    $this->assertEquals(
                        [
                            'component' => 'autocomplete-accountuser',
                            'placeholder' => 'orob2b.account.accountuser.form.choose',
                        ],
                        $options['configs']
                    );
                }
            );

        $this->formType->setDefaultOptions($resolver);
    }
}

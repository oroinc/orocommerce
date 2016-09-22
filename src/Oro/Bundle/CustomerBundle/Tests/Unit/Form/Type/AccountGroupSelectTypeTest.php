<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\CustomerBundle\Form\Type\AccountGroupSelectType;

class AccountGroupSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountGroupSelectType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new AccountGroupSelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(AccountGroupSelectType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('autocomplete_alias', $options);
                        $this->assertArrayHasKey('create_form_route', $options);
                        $this->assertArrayHasKey('configs', $options);
                        $this->assertEquals('oro_account_group', $options['autocomplete_alias']);
                        $this->assertEquals('oro_account_group_create', $options['create_form_route']);
                        $this->assertEquals(
                            ['placeholder' => 'oro.customer.accountgroup.form.choose'],
                            $options['configs']
                        );

                        return true;
                    }
                )
            );

        $this->type->configureOptions($resolver);
    }
}

<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupSelectType;

class CustomerGroupSelectTypeTest extends \PHPUnit_Framework_TestCase
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

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('autocomplete_alias', $options);
                        $this->assertArrayHasKey('create_form_route', $options);
                        $this->assertArrayHasKey('configs', $options);
                        $this->assertEquals('orob2b_account_group', $options['autocomplete_alias']);
                        $this->assertEquals('orob2b_account_group_create', $options['create_form_route']);
                        $this->assertEquals(
                            ['placeholder' => 'orob2b.account.accountgroup.form.choose'],
                            $options['configs']
                        );

                        return true;
                    }
                )
            );

        $this->type->setDefaultOptions($resolver);
    }
}

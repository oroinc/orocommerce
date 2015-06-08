<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;

class PaymentTermSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentTermSelectType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new PaymentTermSelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentTermSelectType::NAME, $this->type->getName());
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
                        $this->assertEquals('orob2b_payment_term', $options['autocomplete_alias']);
                        $this->assertEquals('orob2b_payment_term_create', $options['create_form_route']);
                        $this->assertEquals(
                            ['placeholder' => 'orob2b.payment.paymentterm.form.choose'],
                            $options['configs']
                        );
                        return true;
                    }
                )
            );
        $this->type->setDefaultOptions($resolver);
    }
}

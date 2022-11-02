<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSelectType;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentTermSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTermSelectType
     */
    protected $type;

    protected function setUp(): void
    {
        $this->type = new PaymentTermSelectType();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('autocomplete_alias', $options);
                        $this->assertArrayHasKey('create_form_route', $options);
                        $this->assertArrayHasKey('configs', $options);
                        $this->assertEquals(PaymentTermType::class, $options['autocomplete_alias']);
                        $this->assertEquals('oro_payment_term_create', $options['create_form_route']);
                        $this->assertEquals(
                            ['placeholder' => 'oro.paymentterm.form.choose', 'allowClear' => true],
                            $options['configs']
                        );
                        return true;
                    }
                )
            );
        $this->type->configureOptions($resolver);
    }
}

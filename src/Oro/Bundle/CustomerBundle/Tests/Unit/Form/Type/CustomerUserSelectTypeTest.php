<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;

class CustomerUserSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CustomerUserSelectType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new CustomerUserSelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(CustomerUserSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->formType->getParent());
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals('oro_customer_customer_user', $options['autocomplete_alias']);
                    $this->assertEquals('oro_customer_customer_user_create', $options['create_form_route']);
                    $this->assertEquals(
                        [
                            'component' => 'autocomplete-customeruser',
                            'placeholder' => 'oro.customer.customeruser.form.choose',
                        ],
                        $options['configs']
                    );
                }
            );

        $this->formType->setDefaultOptions($resolver);
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\PayPalBundle\Form\Type\PayPalPasswordType;

class PayPalPasswordTypeTest extends FormIntegrationTestCase
{
    /** @var PayPalPasswordType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new PayPalPasswordType();
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $defaultOptions = $resolver->getDefinedOptions();
        $this->assertArrayNotHasKey('always_empty', $defaultOptions);
    }

    /**
     * @param string|string[] $defaultData
     * @param string|string[] $submittedData
     * @param string|string[] $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $actualData = $form->getData();
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty default data' => [
                'defaultData' => null,
                'submittedData' => 'new password',
                'expectedData' => 'new password',
            ],
            'default data' => [
                'defaultData' => 'test',
                'submittedData' => 'new password',
                'expectedData' => 'new password',
            ],
            'submit placeholder' => [
                'defaultData' => 'test',
                'submittedData' => '******',
                'expectedData' => 'test',
            ],
        ];
    }

    public function testBuildView()
    {
        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getData')->willReturn('password');

        $this->formType->buildView($view, $form, []);

        $this->assertEquals('********', $view->vars['value']);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_paypal_paypal_password_type', $this->formType->getName());
    }
}

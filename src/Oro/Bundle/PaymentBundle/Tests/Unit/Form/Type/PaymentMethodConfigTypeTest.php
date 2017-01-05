<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentMethodConfigTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PaymentMethodConfigType
     */
    protected $formType;

    /** @var PaymentMethodProvidersRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodProvidersRegistry;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    protected function setUp()
    {
        $this->paymentMethodProvidersRegistry = $this->getMockBuilder(PaymentMethodProvidersRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formType = new PaymentMethodConfigType($this->paymentMethodProvidersRegistry, $this->translator);

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PaymentMethodConfigType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $data
     */
    public function testSubmit($data)
    {
        $form = $this->factory->create($this->formType, $data);

        $this->assertSame($data, $form->getData());

        $form->submit([
            'type' => 'MO',
            'options' => ['client_id' => 3],
        ]);

        $paymentMethodConfig = (new PaymentMethodConfig())
            ->setType('MO')
            ->setOptions(['client_id' => 3])
        ;

        $this->assertTrue($form->isValid());
        $this->assertEquals($paymentMethodConfig, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [new PaymentMethodConfig()],
            [
                (new PaymentMethodConfig())->setType('PP')->setOptions(['client_id' => 5])
            ],
        ];
    }
}

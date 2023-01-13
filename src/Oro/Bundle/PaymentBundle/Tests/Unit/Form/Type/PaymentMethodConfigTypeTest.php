<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class PaymentMethodConfigTypeTest extends FormIntegrationTestCase
{
    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var CompositePaymentMethodViewProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodViewProvider;

    /** @var PaymentMethodConfigType */
    private $formType;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentMethodViewProvider = $this->createMock(CompositePaymentMethodViewProvider::class);

        $this->formType = new PaymentMethodConfigType(
            $this->paymentMethodProvider,
            $this->paymentMethodViewProvider
        );

        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PaymentMethodConfigType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(PaymentMethodConfig $data)
    {
        $form = $this->factory->create(PaymentMethodConfigType::class, $data);

        $this->assertSame($data, $form->getData());

        $form->submit([
            'type' => 'MO',
            'options' => ['client_id' => 3],
        ]);

        $paymentMethodConfig = (new PaymentMethodConfig())
            ->setType('MO')
            ->setOptions(['client_id' => 3]);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($paymentMethodConfig, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            [new PaymentMethodConfig()],
            [(new PaymentMethodConfig())->setType('PP')->setOptions(['client_id' => 3])],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }
}

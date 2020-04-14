<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View\Factory;

use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\Factory\BasicPayPalCreditCardPaymentMethodViewFactory;
use Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView;
use Symfony\Component\Form\FormFactoryInterface;

class BasicPayPalCreditCardPaymentMethodViewFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var PaymentTransactionProvider
     */
    private $transactionProvider;

    /**
     * @var BasicPayPalCreditCardPaymentMethodViewFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->transactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->factory = new BasicPayPalCreditCardPaymentMethodViewFactory(
            $this->formFactory,
            $this->transactionProvider
        );
    }

    public function testCreate()
    {
        /** @var PayPalCreditCardConfigInterface $config */
        $config = $this->createMock(PayPalCreditCardConfigInterface::class);

        $expectedView = new PayPalCreditCardPaymentMethodView($this->formFactory, $config, $this->transactionProvider);

        $this->assertEquals($expectedView, $this->factory->create($config));
    }
}

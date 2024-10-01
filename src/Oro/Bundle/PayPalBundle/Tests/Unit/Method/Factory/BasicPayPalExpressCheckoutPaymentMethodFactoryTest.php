<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\BasicPayPalExpressCheckoutPaymentMethodFactory;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\PayPalBundle\Method\Transaction\TransactionOptionProvider;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProvider;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;

class BasicPayPalExpressCheckoutPaymentMethodFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BasicPayPalExpressCheckoutPaymentMethodFactory
     */
    private $factory;

    /**
     * @var Gateway|\PHPUnit\Framework\MockObject\MockObject
     */
    private $gateway;

    /**
     * @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $router;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var OptionsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $optionsProvider;

    /**
     * @var SurchargeProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $surchargeProvider;

    /**
     * @var PropertyAccessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $propertyAccessor;

    /** @var TransactionOptionProvider */
    private $transactionOptionProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->optionsProvider = $this->createMock(OptionsProvider::class);
        $this->surchargeProvider = $this->createMock(SurchargeProvider::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);

        $this->transactionOptionProvider = new TransactionOptionProvider(
            $this->surchargeProvider,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->router,
            $this->propertyAccessor
        );

        $this->factory = new BasicPayPalExpressCheckoutPaymentMethodFactory(
            $this->gateway,
            $this->propertyAccessor,
            $this->transactionOptionProvider
        );
    }

    public function testCreate()
    {
        /** @var PayPalExpressCheckoutConfigInterface $config */
        $config = $this->createMock(PayPalExpressCheckoutConfigInterface::class);
        $this->transactionOptionProvider->setConfig($config);

        $method = new PayPalExpressCheckoutPaymentMethod(
            $this->gateway,
            $config,
            $this->propertyAccessor,
            $this->transactionOptionProvider,
        );

        self::assertEquals($method, $this->factory->create($config));
    }
}

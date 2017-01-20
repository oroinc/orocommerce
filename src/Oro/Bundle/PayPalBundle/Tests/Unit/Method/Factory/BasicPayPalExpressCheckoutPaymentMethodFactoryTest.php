<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\BasicPayPalExpressCheckoutPaymentMethodFactory;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\Routing\RouterInterface;

class BasicPayPalExpressCheckoutPaymentMethodFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ExtractOptionsProvider
     */
    private $optionsProvider;

    /**
     * @var SurchargeProvider
     */
    private $surchargeProvider;

    /**
     * @var BasicPayPalExpressCheckoutPaymentMethodFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->optionsProvider = $this->createMock(ExtractOptionsProvider::class);
        $this->surchargeProvider = $this->createMock(SurchargeProvider::class);
        $this->factory = new BasicPayPalExpressCheckoutPaymentMethodFactory(
            $this->gateway,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider
        );
    }

    public function testCreate()
    {
        /** @var PayPalExpressCheckoutConfigInterface $config */
        $config = $this->createMock(PayPalExpressCheckoutConfigInterface::class);

        $method = new PayPalExpressCheckoutPaymentMethod(
            $this->gateway,
            $config,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider
        );

        $this->assertEquals($method, $this->factory->create($config));
    }
}

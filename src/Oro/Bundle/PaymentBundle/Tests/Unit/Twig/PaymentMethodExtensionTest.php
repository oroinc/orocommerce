<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\PaymentMethodConfigDataEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentBundle\Twig\PaymentMethodExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentMethodExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTransactionProvider;

    /**
     * @var  PaymentMethodLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodLabelFormatter;

    /**
     * @var  EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    /**
     * @var PaymentMethodExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->paymentTransactionProvider = $this
            ->getMockBuilder(PaymentTransactionProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodLabelFormatter = $this
            ->getMockBuilder(PaymentMethodLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this
            ->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new PaymentMethodExtension(
            $this->paymentTransactionProvider,
            $this->paymentMethodLabelFormatter,
            $this->dispatcher
        );
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFunction(
                    'get_payment_methods',
                    [$this->extension, 'getPaymentMethods']
                ),
                new \Twig_SimpleFunction(
                    'get_payment_method_label',
                    [$this->paymentMethodLabelFormatter, 'formatPaymentMethodLabel']
                ),
                new \Twig_SimpleFunction(
                    'get_payment_method_admin_label',
                    [$this->paymentMethodLabelFormatter, 'formatPaymentMethodAdminLabel'],
                    ['is_safe' => ['html']]
                ),
                new \Twig_SimpleFunction(
                    'oro_payment_method_config_template',
                    [$this->extension, 'getPaymentMethodConfigRenderData']
                )
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentMethodExtension::PAYMENT_METHOD_EXTENSION_NAME, $this->extension->getName());
    }

    public function testGetPaymentMethods()
    {
        $entity = new \stdClass();
        $label = 'label';
        $paymentMethodConstant = 'payment_method';
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod($paymentMethodConstant);
        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getPaymentTransactions')
            ->with($entity)
            ->willReturn([$paymentTransaction]);

        $this->paymentMethodLabelFormatter
            ->expects($this->once())
            ->method('formatPaymentMethodLabel')
            ->with($paymentMethodConstant, false)
            ->willReturn($label);

        $this->assertEquals($this->extension->getPaymentMethods($entity), [$label]);
    }

    public function testGetPaymentMethodConfigRenderDataDefault()
    {
        $methodName = 'method_1';

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(PaymentMethodConfigDataEvent::NAME)
            ->will(static::returnCallback(function ($name, PaymentMethodConfigDataEvent $event) use ($methodName) {
                static::assertEquals($methodName, $event->getMethodIdentifier());
                $event->setTemplate(PaymentMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE);
            }));

        self::assertEquals(
            PaymentMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE,
            $this->extension->getPaymentMethodConfigRenderData($methodName)
        );

        //test cache
        self::assertEquals(
            PaymentMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE,
            $this->extension->getPaymentMethodConfigRenderData($methodName)
        );
    }

    public function testGetPaymentMethodConfigRenderData()
    {
        $methodName = 'method_1';
        $template = 'Bundle:template.html.twig';

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(PaymentMethodConfigDataEvent::NAME)
            ->will(static::returnCallback(
                function ($name, PaymentMethodConfigDataEvent $event) use ($methodName, $template) {
                    static::assertEquals($methodName, $event->getMethodIdentifier());
                    $event->setTemplate($template);
                }
            ));

        self::assertEquals($template, $this->extension->getPaymentMethodConfigRenderData($methodName));
    }
}

<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermMethodViewProvider;

use Symfony\Component\Translation\TranslatorInterface;

class PaymentTermMethodViewProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTermProvider;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var PaymentTermConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var PaymentTermMethodViewProvider
     */
    protected $viewProvider;

    /**
     * @var PaymentTermView
     */
    protected $methodView;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder(PaymentTermProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->config = $this->createMock(PaymentTermConfigInterface::class);
        $this->viewProvider = new PaymentTermMethodViewProvider(
            $this->paymentTermProvider,
            $this->translator,
            $this->config
        );
        $this->methodView = new PaymentTermView(
            $this->paymentTermProvider,
            $this->translator,
            $this->config
        );
    }

    public function testGetPaymentMethodViews()
    {
        $paymentMethods = [PaymentTerm::TYPE];
        static::assertEquals(
            [PaymentTerm::TYPE => $this->methodView],
            $this->viewProvider->getPaymentMethodViews($paymentMethods)
        );
    }

    public function testGetPaymentMethodView()
    {
        static::assertEquals($this->methodView, $this->viewProvider->getPaymentMethodView(PaymentTerm::TYPE));
    }

    public function testHasPaymentMethodView()
    {
        static::assertTrue($this->viewProvider->hasPaymentMethodView(PaymentTerm::TYPE));
        static::assertFalse($this->viewProvider->hasPaymentMethodView('not_existing'));
    }
}

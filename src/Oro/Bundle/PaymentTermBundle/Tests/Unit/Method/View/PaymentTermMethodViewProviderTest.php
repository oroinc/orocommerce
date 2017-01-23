<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermMethodViewProvider;
use Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
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
    * @var PaymentTermConfigInterface[]|\PHPUnit_Framework_MockObject_MockObject
    */
    private $configs;

    /**
     * @var PaymentTermMethodViewProvider
     */
    protected $viewProvider;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder(PaymentTermProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->createMock(TranslatorInterface::class);

        /** @var PaymentTermConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
        $configProvider = $this->createMock(PaymentTermConfigProviderInterface::class);
        $this->configs = [
            $this->createMock(PaymentTermConfigInterface::class),
            $this->createMock(PaymentTermConfigInterface::class),
        ];
        $configProvider->expects(static::any())
            ->method('getPaymentConfigs')
            ->willReturn($this->configs);

        $this->viewProvider = new PaymentTermMethodViewProvider(
            $this->paymentTermProvider,
            $this->translator,
            $configProvider
        );
    }

    public function testGetPaymentMethodViewsReturnsCorrectObjects()
    {
        $view1 = new PaymentTermView($this->paymentTermProvider, $this->translator, $this->configs[0]);
        $view2 = new PaymentTermView($this->paymentTermProvider, $this->translator, $this->configs[1]);
        $identifiers = [
            $view1->getPaymentMethodIdentifier(),
            $view2->getPaymentMethodIdentifier(),
            'wrong',
        ];
        static::assertEquals([$view1, $view2], $this->viewProvider->getPaymentMethodViews($identifiers));
    }

    public function testGetPaymentMethodViewReturnsCorrectObject()
    {
        $view = new PaymentTermView($this->paymentTermProvider, $this->translator, $this->configs[0]);
        static::assertEquals(
            $view,
            $this->viewProvider->getPaymentMethodView($view->getPaymentMethodIdentifier())
        );
    }

    public function testGetPaymentMethodViewForWrongIdentifier()
    {
        static::assertNull($this->viewProvider->getPaymentMethodView('wrong'));
    }

    public function testHasPaymentMethodViewForCorrectIdentifier()
    {
        $view = new PaymentTermView($this->paymentTermProvider, $this->translator, $this->configs[0]);
        static::assertTrue($this->viewProvider->hasPaymentMethodView($view->getPaymentMethodIdentifier()));
    }

    public function testHasPaymentMethodViewForWrongIdentifier()
    {
        static::assertFalse($this->viewProvider->hasPaymentMethodView('wrong'));
    }
}

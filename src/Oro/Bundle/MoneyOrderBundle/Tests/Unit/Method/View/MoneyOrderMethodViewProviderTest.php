<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\View;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProvider;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderMethodViewProvider;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;

class MoneyOrderMethodViewProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MoneyOrderMethodViewProvider
     */
    private $provider;

    /**
     * @var MoneyOrderConfig[]|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configs;

    protected function setUp()
    {
        $this->configs = [
            $this->createMock(MoneyOrderConfig::class),
            $this->createMock(MoneyOrderConfig::class),
        ];

        $configProvider = $this->createMock(MoneyOrderConfigProvider::class);
        $configProvider->expects(static::any())
            ->method('getPaymentConfigs')
            ->willReturn($this->configs);

        $this->provider = new MoneyOrderMethodViewProvider($configProvider);
    }

    public function testGetPaymentMethodViewsReturnsCorrectObjects()
    {
        $view1 = new MoneyOrderView($this->configs[0]);
        $view2 = new MoneyOrderView($this->configs[1]);

        $identifiers = [
            $view1->getPaymentMethodIdentifier(),
            $view2->getPaymentMethodIdentifier(),
            'wrong',
        ];

        static::assertEquals([$view1, $view2], $this->provider->getPaymentMethodViews($identifiers));
    }

    public function testGetPaymentMethodViewReturnsCorrectObject()
    {
        $view = new MoneyOrderView($this->configs[0]);

        static::assertEquals(
            $view,
            $this->provider->getPaymentMethodView($view->getPaymentMethodIdentifier())
        );
    }

    public function testGetPaymentMethodViewForWrongIdentifier()
    {
        static::assertNull($this->provider->getPaymentMethodView('wrong'));
    }

    public function testHasPaymentMethodViewForCorrectIdentifier()
    {
        $view = new MoneyOrderView($this->configs[0]);

        static::assertTrue($this->provider->hasPaymentMethodView($view->getPaymentMethodIdentifier()));
    }

    public function testHasPaymentMethodViewForWrongIdentifier()
    {
        static::assertFalse($this->provider->hasPaymentMethodView('wrong'));
    }
}

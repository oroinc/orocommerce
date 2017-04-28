<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit;

use Oro\Bundle\ApruveBundle\Provider\ShippingAmountProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;

class ShippingAmountProviderTest extends \PHPUnit_Framework_TestCase
{
    const AMOUNT = 10.0;

    /**
     * @var \stdClass|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sourceEntity;

    /**
     * @var Surcharge|\PHPUnit_Framework_MockObject_MockObject
     */
    private $surcharge;

    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContext;

    /**
     * @var SurchargeProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $surchargeProvider;

    /**
     * @var ShippingAmountProvider
     */
    private $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->sourceEntity = $this->createMock(\stdClass::class);

        $this->paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->paymentContext
            ->expects(static::once())
            ->method('getSourceEntity')
            ->willReturn($this->sourceEntity);

        $this->surcharge = $this->createMock(Surcharge::class);
        $this->surcharge
            ->expects(static::once())
            ->method('getShippingAmount')
            ->willReturn(self::AMOUNT);

        $this->surchargeProvider = $this->createMock(SurchargeProvider::class);

        $this->surchargeProvider
            ->expects(static::once())
            ->method('getSurcharges')
            ->with($this->sourceEntity)
            ->willReturn($this->surcharge);

        $this->provider = new ShippingAmountProvider($this->surchargeProvider);
    }

    public function testGetShippingAmount()
    {
        $actual = $this->provider->getShippingAmount($this->paymentContext);

        static::assertSame(self::AMOUNT, $actual);
    }
}

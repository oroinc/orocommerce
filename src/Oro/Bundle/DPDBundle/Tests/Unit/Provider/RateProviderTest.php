<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class RateProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DPDTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var RateProvider
     */
    protected $rateProvider;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var MeasureUnitConversion|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $measureUnitConversion;

    protected function setUp()
    {
        $this->transport = $this->createMock(DPDTransport::class);

        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMock();

        $this->measureUnitConversion = $this->getMockBuilder(MeasureUnitConversion::class)
            ->disableOriginalConstructor()->getMock();
        $this->measureUnitConversion->expects(static::any())->method('convert')->willReturnCallback(
            function () {
                $args = func_get_args();

                return $args[0];
            }
        );

        $this->rateProvider = new RateProvider(
            $this->registry,
            $this->measureUnitConversion
        );
    }
}

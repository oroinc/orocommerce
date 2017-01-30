<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Method\Factory\DPDShippingMethodFactory;
use Oro\Bundle\DPDBundle\Method\Factory\DPDShippingMethodTypeFactoryInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;

class DPDShippingMethodFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DPDTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transport;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localizationHelper;

    /**
     * @var IntegrationMethodIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodIdentifierGenerator;

    /**
     * @var DPDShippingMethodTypeFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodTypeFactory;

    /**
     * @var DPDShippingMethodFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    protected function setUp()
    {
        $this->transport = $this->createMock(DPDTransport::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->methodIdentifierGenerator = $this->createMock(IntegrationMethodIdentifierGeneratorInterface::class);
        $this->methodTypeFactory = $this->createMock(DPDShippingMethodTypeFactoryInterface::class);

        $this->factory = new DPDShippingMethodFactory(
            $this->transport,
            $this->localizationHelper,
            $this->methodIdentifierGenerator,
            $this->methodTypeFactory
        );
    }

    public function testCreate()
    {
        $identifier = 'dpd_1';
        $labelsCollection = $this->createMock(Collection::class);

        /** @var DPDSettings|\PHPUnit_Framework_MockObject_MockObject $settings */
        $settings = $this->createMock(DPDSettings::class);

        $settings->expects($this->once())
            ->method('getLabels')
            ->willReturn($labelsCollection);

        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->any())
            ->method('getTransport')
            ->willReturn($settings);

        $type1 = $this->createMock(DPDShippingMethodType::class);
        $type2 = $this->createMock(DPDShippingMethodType::class);

        $service1 = $this->createMock(ShippingService::class);
        $service2 = $this->createMock(ShippingService::class);

        $this->methodTypeFactory->expects($this->at(0))
            ->method('create')
            ->with($channel, $service1)
            ->willReturn($type1);

        $this->methodTypeFactory->expects($this->at(1))
            ->method('create')
            ->with($channel, $service2)
            ->willReturn($type2);

        $serviceCollection = $this->createMock(Collection::class);
        $serviceCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([$service1, $service2]);

        $settings->expects($this->once())
            ->method('getApplicableShippingServices')
            ->willReturn($serviceCollection);

        $this->methodIdentifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->with($labelsCollection)
            ->willReturn('en');

        $this->assertEquals(new DPDShippingMethod(
            $identifier,
            'en',
            [$type1, $type2],
            $settings,
            $this->transport
        ), $this->factory->create($channel));
    }
}

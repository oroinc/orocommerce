<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Method\Factory\DPDShippingMethodTypeFactory;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;

class DPDShippingMethodTypeFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DPDMethodTypeIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $typeIdentifierGenerator;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodIdentifierGenerator;

    /**
     * @var DPDTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transport;

    /**
     * @var PackageProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $packageProvider;

    /**
     * @var RateProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rateProvider;

    /**
     * @var DPDShippingMethodTypeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    protected function setUp()
    {
        $this->typeIdentifierGenerator = $this->createMock(DPDMethodTypeIdentifierGeneratorInterface::class);
        $this->methodIdentifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->transport = $this->createMock(DPDTransport::class);
        $this->packageProvider = $this->createMock(PackageProvider::class);
        $this->rateProvider = $this->createMock(RateProvider::class);

        $this->factory = new DPDShippingMethodTypeFactory(
            $this->typeIdentifierGenerator,
            $this->methodIdentifierGenerator,
            $this->transport,
            $this->packageProvider,
            $this->rateProvider
        );
    }

    public function testCreate()
    {
        $identifier = 'dpd_1_59';
        $methodId = 'dpd_1';

        /** @var DPDSettings|\PHPUnit_Framework_MockObject_MockObject $settings */
        $settings = $this->createMock(DPDSettings::class);

        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->any())
            ->method('getTransport')
            ->willReturn($settings);

        /** @var ShippingService|\PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->createMock(ShippingService::class);

        $service->expects($this->once())
            ->method('getDescription')
            ->willReturn('air');

        $this->methodIdentifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($methodId);

        $this->typeIdentifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel, $service)
            ->willReturn($identifier);

        $this->assertEquals(new DPDShippingMethodType(
            $identifier,
            'air',
            $methodId,
            $service,
            $settings,
            $this->transport,
            $this->packageProvider,
            $this->rateProvider
        ), $this->factory->create($channel, $service));
    }
}

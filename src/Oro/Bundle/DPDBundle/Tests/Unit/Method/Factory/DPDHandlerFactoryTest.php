<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\DPDBundle\Method\DPDHandler;
use Oro\Bundle\DPDBundle\Method\Factory\DPDHandlerFactory;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Method\Factory\DPDShippingMethodTypeFactory;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;

class DPDHandlerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DPDMethodTypeIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $typeIdentifierGenerator;

    /**
     * @var DPDTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transport;

    /**
     * @var PackageProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $packageProvider;

    /**
     * @var DPDRequestFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dpdRequestFactory;

    /**
     * @var ZipCodeRulesCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $zipCodeRulesCache;

    /**
     * @var OrderShippingLineItemConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingLineItemConverter;

    /**
     * @var DPDShippingMethodTypeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    protected function setUp()
    {
        $this->typeIdentifierGenerator = $this->createMock(DPDMethodTypeIdentifierGeneratorInterface::class);
        $this->transport = $this->createMock(DPDTransport::class);
        $this->packageProvider = $this->createMock(PackageProvider::class);
        $this->dpdRequestFactory = $this->createMock(DPDRequestFactory::class);
        $this->zipCodeRulesCache = $this->createMock(ZipCodeRulesCache::class);
        $this->shippingLineItemConverter = $this->createMock(OrderShippingLineItemConverterInterface::class);

        $this->factory = new DPDHandlerFactory(
            $this->typeIdentifierGenerator,
            $this->transport,
            $this->packageProvider,
            $this->dpdRequestFactory,
            $this->zipCodeRulesCache,
            $this->shippingLineItemConverter
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

        $this->typeIdentifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel, $service)
            ->willReturn($identifier);

        $this->assertEquals(new DPDHandler(
            $identifier,
            $service,
            $settings,
            $this->transport,
            $this->packageProvider,
            $this->dpdRequestFactory,
            $this->zipCodeRulesCache,
            $this->shippingLineItemConverter
        ), $this->factory->create($channel, $service));
    }
}

<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory\FedexShippingMethodTypeFactory;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethodType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier\FedexMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use PHPUnit\Framework\TestCase;

class FedexShippingMethodTypeFactoryTest extends TestCase
{
    const IDENTIFIER = 'id';
    const LABEL = 'label';

    /**
     * @var FedexMethodTypeIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $identifierGenerator;

    /**
     * @var FedexShippingMethodTypeFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->identifierGenerator = $this->createMock(FedexMethodTypeIdentifierGeneratorInterface::class);

        $this->factory = new FedexShippingMethodTypeFactory($this->identifierGenerator);
    }

    public function testCreate()
    {
        $settings = new FedexIntegrationSettings();

        $channel = new Channel();
        $channel->setTransport($settings);

        $service = new ShippingService();
        $service->setDescription(self::LABEL);

        $this->identifierGenerator
            ->expects(static::once())
            ->method('generate')
            ->with($service)
            ->willReturn(self::IDENTIFIER);

        static::assertEquals(
            new FedexShippingMethodType(
                self::IDENTIFIER,
                self::LABEL,
                $settings
            ),
            $this->factory->create($channel, $service)
        );
    }
}

<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Factory\MultiShippingMethodFromChannelFactory;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class MultiShippingMethodFromChannelFactoryTest extends TestCase
{
    private IntegrationIdentifierGeneratorInterface|MockObject $identifierGenerator;
    private TranslatorInterface|MockObject $translator;
    private RoundingServiceInterface|MockObject $roundingService;
    private MultiShippingCostProvider|MockObject $shippingCostProvider;
    private MultiShippingMethodFromChannelFactory $factory;

    protected function setUp(): void
    {
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->shippingCostProvider = $this->createMock(MultiShippingCostProvider::class);

        $this->factory = new MultiShippingMethodFromChannelFactory(
            $this->identifierGenerator,
            $this->translator,
            $this->roundingService,
            $this->shippingCostProvider
        );
    }

    public function testCreate()
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, 7);
        $channel->setEnabled(true);

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn('test_type_1');

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('oro.shipping.multi_shipping_method.label');

        $method = $this->factory->create($channel);

        $this->assertInstanceOf(MultiShippingMethod::class, $method);
        $this->assertEquals('test_type_1', $method->getIdentifier());
        $this->assertEquals('oro.shipping.multi_shipping_method.label', $method->getLabel());
        $this->assertEquals('bundles/oroshipping/img/multi-shipping-logo.png', $method->getIcon());
        $this->assertTrue($method->isEnabled());
    }
}

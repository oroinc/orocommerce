<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Factory;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Factory\MultiShippingMethodFromChannelFactory;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class MultiShippingMethodFromChannelFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $identifierGenerator;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $roundingService;

    /** @var MultiShippingCostProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingCostProvider;

    /** @var MultiShippingMethodFromChannelFactory */
    private $factory;

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

    public function testCreate(): void
    {
        $identifier = 'test_type_1';
        $enabled = true;
        $label = 'label';

        $channel = new Channel();
        $channel->setEnabled($enabled);

        $this->identifierGenerator->expects(self::once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('oro.shipping.multi_shipping_method.label')
            ->willReturn($label);

        $expected = new MultiShippingMethod(
            $identifier,
            $label,
            'bundles/oroshipping/img/multi-shipping-logo.png',
            $enabled,
            $this->roundingService,
            $this->shippingCostProvider
        );
        self::assertEquals($expected, $this->factory->create($channel));
    }
}

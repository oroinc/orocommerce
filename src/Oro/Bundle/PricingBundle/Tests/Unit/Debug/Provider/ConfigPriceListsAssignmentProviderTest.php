<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Debug\Provider\ConfigPriceListsAssignmentProvider;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigPriceListsAssignmentProviderTest extends TestCase
{
    use EntityTrait;

    private ConfigManager|MockObject $configManager;
    private PriceListConfigConverter|MockObject $configConverter;
    private TranslatorInterface|MockObject $translator;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private ConfigPriceListsAssignmentProvider $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configConverter = $this->createMock(PriceListConfigConverter::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->provider = new ConfigPriceListsAssignmentProvider(
            $this->configManager,
            $this->configConverter,
            $this->translator,
            $this->urlGenerator
        );
    }

    public function testGetPriceListAssignments()
    {
        $relations = [
            [
                PriceListConfigConverter::PRICE_LIST_KEY => 1,
                PriceListConfigConverter::MERGE_KEY => true,
                PriceListConfigConverter::SORT_ORDER_KEY => 10
            ]
        ];
        $convertedRelations = [
            (new PriceListConfig())
                ->setPriceList($this->getEntity(PriceList::class, ['id' => 1]))
                ->setSortOrder(10)
                ->setMergeAllowed(true)
        ];

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.default_price_lists')
            ->willReturn($relations);

        $this->configConverter->expects($this->once())
            ->method('convertFromSaved')
            ->with($relations)
            ->willReturn($convertedRelations);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(fn ($str) => $str . ' TR');

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'oro_config_configuration_system',
                ['activeGroup' => 'commerce', 'activeSubGroup' => 'pricing']
            )
            ->willReturn('/system-config-url');

        $expected = [
            'section_title' => 'oro.config.menu.system_configuration.label TR',
            'link' => '/system-config-url',
            'link_title' => 'oro.config.module_label TR',
            'fallback' => null,
            'fallback_entity_title' => null,
            'price_lists' => $convertedRelations,
            'stop' => false
        ];

        $this->assertEquals($expected, $this->provider->getPriceListAssignments());
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Debug\Provider\WebsitePriceListsAssignmentProvider;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebsitePriceListsAssignmentProviderTest extends TestCase
{
    use EntityTrait;

    private DebugProductPricesPriceListRequestHandler|MockObject $requestHandler;
    private ManagerRegistry|MockObject $registry;
    private TranslatorInterface|MockObject $translator;

    private WebsitePriceListsAssignmentProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestHandler = $this->createMock(DebugProductPricesPriceListRequestHandler::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new WebsitePriceListsAssignmentProvider(
            $this->requestHandler,
            $this->registry,
            $this->translator
        );
    }

    /**
     * @dataProvider infoDataProvider
     */
    public function testGetPriceListAssignments(
        ?PriceListWebsiteFallback $fallbackEntity,
        string $expectedFallback,
        ?string $expectedFallbackTitle,
        bool $expectedStop
    ) {
        $website = $this->getEntity(Website::class, ['id' => 10]);
        $website->setName('Test Name');

        $this->requestHandler->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);

        $relations = [
            (new PriceListToCustomer())
                ->setPriceList($this->getEntity(PriceList::class, ['id' => 1]))
                ->setSortOrder(10)
                ->setMergeAllowed(true)
        ];

        $entityRepo = $this->createMock(PriceListToWebsiteRepository::class);
        $entityRepo->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'website' => $website
                ],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            )
            ->willReturn($relations);
        $fallbackRepo = $this->createMock(PriceListWebsiteFallbackRepository::class);
        $fallbackRepo->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'website' => $website
                ]
            )
            ->willReturn($fallbackEntity);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListToWebsite::class, null, $entityRepo],
                [PriceListWebsiteFallback::class, null, $fallbackRepo]
            ]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($str) => $str . ' TR');

        $expected = [
            'section_title' => 'oro.website.entity_label TR',
            'link' => null,
            'link_title' => 'Test Name',
            'fallback' => $expectedFallback,
            'fallback_entity_title' => $expectedFallbackTitle,
            'price_lists' => $relations,
            'stop' => $expectedStop
        ];

        $this->assertEquals($expected, $this->provider->getPriceListAssignments());
    }

    public static function infoDataProvider(): array
    {
        return [
            [
                null,
                'oro.pricing.fallback.config.label',
                'oro.config.module_label TR',
                false
            ],
            [
                (new PriceListWebsiteFallback())->setFallback(PriceListWebsiteFallback::CONFIG),
                'oro.pricing.fallback.config.label',
                'oro.config.module_label TR',
                false
            ],
            [
                (new PriceListWebsiteFallback())
                    ->setFallback(PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY),
                'oro.pricing.fallback.current_website_only.label',
                null,
                true
            ]
        ];
    }
}

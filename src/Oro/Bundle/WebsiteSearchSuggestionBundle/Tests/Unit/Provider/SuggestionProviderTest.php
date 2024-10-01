<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Provider\ProductsProvider;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Provider\SuggestionProvider;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Splitter\PhraseSplitter;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

final class SuggestionProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private SuggestionProvider $suggestionProvider;

    private PhraseSplitter&MockObject $phraseSplitter;

    private ProductsProvider&MockObject $productsProvider;

    private LocalizationHelper&MockObject $localizationHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->phraseSplitter = $this->createMock(PhraseSplitter::class);
        $this->productsProvider = $this->createMock(ProductsProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->suggestionProvider = new SuggestionProvider(
            $this->productsProvider,
            $this->phraseSplitter,
            $this->localizationHelper
        );
    }

    /**
     * @dataProvider skuAndNamesProvider
     */
    public function testThatPhrasesGeneratedForEachLocalization(array $skuAndNames): void
    {
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        $productName = $this->getEntity(ProductName::class, ['string' => 'name']);

        $this->productsProvider
            ->expects(self::once())
            ->method('getProductsSkuAndNames')
            ->willReturn($skuAndNames);

        $this->localizationHelper
            ->expects(self::once())
            ->method('getLocalizations')
            ->willReturn([$localization]);

        $this->localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturn($productName);

        $this->phraseSplitter
            ->expects(self::any())
            ->method('split')
            ->willReturnMap([
                ['sku', ['sku_phrase']],
                ['sku2', ['sku2_phrase']],
                ['name', ['name_phrase']],
                ['name2', ['name_phrase2']],
            ]);

        $result = $this->suggestionProvider->getLocalizedSuggestionPhrasesGroupedByProductId(
            array_column($skuAndNames, 'id')
        );

        self::assertEquals(
            [
                1 => [
                    "sku_phrase" => [1],
                    "name_phrase" => [1, 2,],
                    "sku2_phrase" => [2]
                ]
            ],
            $result
        );
    }

    private function skuAndNamesProvider(): array
    {
        return [
            [
                [
                    1 => [
                        'sku' => 'sku',
                        'names' => [new \StdClass()]
                    ],
                    2 => [
                        'sku' => 'sku2',
                        'names' => [new \StdClass()],
                    ],
                ]
            ]
        ];
    }
}

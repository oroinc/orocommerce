<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Splitter\PhraseSplitter;

/**
 * Provides localized suggestion phrases
 */
class SuggestionProvider
{
    public function __construct(
        private ProductsProvider $productsProvider,
        private PhraseSplitter   $phraseSplitter,
        private LocalizationHelper $localizationHelper,
        private int $phraseChunkSize
    ) {
    }

    public function getLocalizedSuggestionPhrasesGroupedByProductId(
        array $productIds,
        int $chunkSize = null
    ): \Generator {
        $chunkSize = $chunkSize ?? $this->phraseChunkSize;
        $productsSkuNames = $this->productsProvider->getProductsSkuAndNames($productIds);
        $localizations = $this->localizationHelper->getLocalizations();

        foreach ($localizations as $localization) {
            $result = [];
            foreach ($productsSkuNames as $productId => $textGroup) {
                $productName = $this->localizationHelper->getLocalizedValue(
                    new ArrayCollection($textGroup['names']),
                    $localization
                );

                $phrases = \array_merge(
                    $this->phraseSplitter->split($textGroup['sku']),
                    $this->phraseSplitter->split($productName->getString())
                );

                foreach ($phrases as $phrase) {
                    $result[$phrase][$productId] = $productId;
                }

                if (\count($result) > $chunkSize) {
                    yield $localization->getId() => $result;
                    $result = [];
                }
            }

            if (!empty($result)) {
                yield $localization->getId() => $result;
            }
        }
    }
}

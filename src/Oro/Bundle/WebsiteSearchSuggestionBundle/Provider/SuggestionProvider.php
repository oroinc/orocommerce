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
    ) {
    }

    public function getLocalizedSuggestionPhrasesGroupedByProductId(array $productIds): array
    {
        $productsByLocalizedPhrase = [];

        $productsSkuNames = $this->productsProvider->getProductsSkuAndNames($productIds);

        $localizations = $this->localizationHelper->getLocalizations();

        foreach ($productsSkuNames as $productId => $textGroup) {
            foreach ($localizations as $localization) {
                $productName = $this->localizationHelper->getLocalizedValue(
                    new ArrayCollection($textGroup['names']),
                    $localization
                );

                $phrases = array_merge(
                    $this->phraseSplitter->split($textGroup['sku']),
                    $this->phraseSplitter->split($productName->getString())
                );

                foreach ($phrases as $phrase) {
                    $productsByLocalizedPhrase[$localization->getId()][$phrase][] = $productId;
                }
            }
        }

        return $productsByLocalizedPhrase;
    }
}

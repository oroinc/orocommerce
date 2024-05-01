<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\WebsiteSearch;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchSuggestionBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Query\ProductSuggestionRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds category aggregation to product autocomplete
 */
class AddSuggestToProductAutocompleteListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    private const PRODUCT_SEARCH_ROUTE_NAME = 'oro_product_frontend_product_search';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ProductSuggestionRepository $suggestionRepository,
        private LocalizationIdPlaceholder $localizationIdPlaceholder,
        private ConfigManager $configManager,
    ) {
    }

    public function onProcessAutocompleteData(ProcessAutocompleteDataEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $data = $event->getData();
        $maxNumber = $this->getMaxNumberProductSuggestions();
        $suggests = [];

        if ($maxNumber > 0) {
            $suggests = $this->getSuggestsData($event->getQueryString(), $maxNumber);
        }

        $data['suggests'] = $suggests;
        $event->setData($data);
    }

    private function getSuggestsData(string $queryString, int $maxNumber): array
    {
        $query = $this->suggestionRepository->getAutocompleteSuggestsSearchQuery(
            $queryString,
            (int)$this->localizationIdPlaceholder->getDefaultValue(),
            $maxNumber
        );

        $query->setHint(Suggestion::HINT_SEARCH_SUGGESTION, $queryString);

        $suggests = [];
        foreach ($query->getResult() as $result) {
            $data = $result->getSelectedData();
            $data['url'] = $this->urlGenerator->generate(self::PRODUCT_SEARCH_ROUTE_NAME, [
                'search' => $data['phrase']
            ]);
            $suggests[] = $data;
        }

        return $suggests;
    }

    private function getMaxNumberProductSuggestions(): int
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(
            Configuration::SEARCH_AUTOCOMPLETE_MAX_SUGGESTS
        ));
    }
}

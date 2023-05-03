<?php

namespace Oro\Bundle\CatalogBundle\EventListener\Search;

use Oro\Bundle\CatalogBundle\DependencyInjection\Configuration as CatalogConfiguration;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteQueryEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds category aggregation to product autocomplete
 */
class AddCategoryToProductAutocompleteListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private HtmlTagExtension $htmlTagExtension,
        private ConfigManager $configManager,
    ) {
    }

    public function onProcessAutocompleteQuery(ProcessAutocompleteQueryEvent $event) : void
    {
        $numberOfCategories = $this->configManager
            ->get(CatalogConfiguration::getConfigKeyByName(CatalogConfiguration::SEARCH_AUTOCOMPLETE_MAX_CATEGORIES));

        if ($numberOfCategories > 0) {
            $event->getQuery()->addAggregate(
                'category',
                'text.category_id_with_parent_categories_LOCALIZATION_ID',
                Query::AGGREGATE_FUNCTION_COUNT,
                [Query::AGGREGATE_PARAMETER_MAX => $numberOfCategories]
            );
        }
    }

    public function onProcessAutocompleteData(ProcessAutocompleteDataEvent $event) : void
    {
        $categories = $this->getCategoryData($event->getResult()->getAggregatedData(), $event->getQueryString());

        $data = $event->getData();
        $data['categories'] = $this->sanitize($categories);
        $event->setData($data);
    }

    protected function getCategoryData(array $aggregations, string $queryString) : array
    {
        $categoryAggregation = $aggregations['category'] ?? [];
        $categoryData = [];
        foreach ($categoryAggregation as $categoryLine => $count) {
            $categoryTree = explode(Category::INDEX_DATA_DELIMITER, $categoryLine);
            $categoryId = (int)$categoryTree[0];
            unset($categoryTree[0]);

            $url = $this->urlGenerator->generate(
                'oro_product_frontend_product_search',
                [
                    'search' => $queryString,
                    'categoryId' => $categoryId
                ]
            );

            // category ID is not used as a key to keep proper order at the storefront
            $categoryData[] = [
                'id' => $categoryId,
                'url' => $url,
                'tree' => array_values($categoryTree),
                'count' => (int)$count
            ];
        }

        return $categoryData;
    }

    protected function sanitize(array $data) : array
    {
        foreach ($data as $categoryKey => $category) {
            foreach ($category['tree'] as $treeKey => $treeTitle) {
                $data[$categoryKey]['tree'][$treeKey] = $this->htmlTagExtension->htmlSanitize($treeTitle);
            }
        }

        return $data;
    }
}

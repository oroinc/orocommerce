<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Handler\RequestContentVariantHandler;
use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;

/**
 * Provides default grid data for export, including current category id and includeSubcategories parameters.
 */
class FrontendProductExportOptionsProvider
{
    private RequestProductHandler $requestProductHandler;
    private SearchProductHandler $searchProductHandler;
    private RequestContentVariantHandler $requestHandler;

    public function __construct(
        RequestProductHandler $requestProductHandler,
        SearchProductHandler $searchProductHandler,
        RequestContentVariantHandler $requestHandler
    ) {
        $this->requestProductHandler = $requestProductHandler;
        $this->searchProductHandler = $searchProductHandler;
        $this->requestHandler = $requestHandler;
    }

    /**
     * Get default filteredResultsGridParams to provide correct data grid filtering by category id if no any filters
     * were chosen.
     */
    public function getDefaultGridExportRequestOptions(): ?string
    {
        $parameters = new ParameterBag();

        $this->addCategoryParameters($parameters);
        $this->addSearchParameters($parameters);
        $this->addContentVariantParams($parameters);

        $params = $parameters->all();

        if (empty($params)) {
            return null;
        }

        return http_build_query(['g' => $params]);
    }

    /**
     * Check if product datagrid supports export functionality.
     */
    public function getExportAvailableForProductGrid(array $gridContext): bool
    {
        return array_key_exists('frontend-product-search-grid', $gridContext);
    }

    private function addCategoryParameters(ParameterBag $parameters): void
    {
        $categoryId = $this->requestProductHandler->getCategoryId();

        if ($categoryId) {
            $parameters->set('categoryId', $categoryId);
            $parameters->set('includeSubcategories', $this->requestProductHandler->getIncludeSubcategoriesChoice());

            if ($categoryContentVariantId = $this->requestProductHandler->getCategoryContentVariantId()) {
                $parameters->set(
                    CategoryPageContentVariantType::CATEGORY_CONTENT_VARIANT_ID_KEY,
                    $categoryContentVariantId
                );

                $parameters->set(
                    CategoryPageContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY,
                    $this->isOverrideConfiguration()
                );
            }
        }
    }

    private function addSearchParameters(ParameterBag $parameters): void
    {
        $searchString = $this->searchProductHandler->getSearchString();
        if ($searchString) {
            $parameters->set('search', $searchString);
        }
    }

    private function addContentVariantParams(ParameterBag $parameters): void
    {
        $contentVariantId = $this->requestHandler->getContentVariantId();

        if ($contentVariantId) {
            $parameters->set(ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY, $contentVariantId);
            $parameters->set(
                ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY,
                $this->requestHandler->getOverrideVariantConfiguration()
            );
        }
    }

    private function isOverrideConfiguration(): bool
    {
        $overrideVariantConfiguration = $this->requestProductHandler->getOverrideVariantConfiguration();
        return filter_var($overrideVariantConfiguration, FILTER_VALIDATE_BOOLEAN);
    }
}

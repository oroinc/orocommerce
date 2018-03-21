<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\QueryString\QueryStringProvider;

/**
 * This class represents route and route parameters that will be used in WebsiteSearchBundle:WebsiteSearchController
 * for redirecting request
 *
 * @package Oro\Bundle\ProductBundle\Provider
 */
class ProductWebsiteSearchTypeProvider implements WebsiteSearchTypeInterface
{
    protected const ROUTE = 'oro_product_frontend_product_index';

    /**
     * {@inheritdoc}
     */
    public function getRoute(string $searchString = ''): string
    {
        return self::ROUTE;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.product.frontend.website_search_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteParameters(string $searchString = ''): array
    {
        $urlParams = [];
        if ($searchString) {
            $urlParams['grid']['frontend-product-search-grid'] = http_build_query(
                [
                    AbstractFilterExtension::MINIFIED_FILTER_PARAM => [
                        'all_text' => ['value' => $searchString, 'type' => TextFilterType::TYPE_CONTAINS],
                    ],
                ]
            );

            $urlParams[QueryStringProvider::QUERY_PARAM] = $searchString;
            $urlParams[QueryStringProvider::TYPE_PARAM] = 'product';
        }

        return $urlParams;
    }
}

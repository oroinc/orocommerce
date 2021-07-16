<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\FinderDatabaseStrategy as RelatedProductFinderDatabaseStrategy;
use Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct\FinderDatabaseStrategy as UpsellProductFinderDatabaseStrategy;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to get product related/upsell items IDs, check if product type is configurable, generate
 * form ID for line items form and get data for rendering autocomplete input:
 *   - oro_product_expression_autocomplete_data
 *   - is_configurable_product_type
 *   - get_upsell_products_ids
 *   - get_related_products_ids
 */
class ProductExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const NAME = 'oro_product';

    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return AutocompleteFieldsProvider
     */
    protected function getAutocompleteFieldsProvider()
    {
        return $this->container->get('oro_product.autocomplete_fields_provider');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_product_expression_autocomplete_data',
                [$this, 'getAutocompleteData']
            ),
            new TwigFunction(
                'is_configurable_product_type',
                [$this, 'isConfigurableType']
            ),
            new TwigFunction(
                'get_upsell_products_ids',
                [$this, 'getUpsellProductsIds']
            ),
            new TwigFunction(
                'get_related_products_ids',
                [$this, 'getRelatedProductsIds']
            ),
        ];
    }

    /**
     * @param Product $product
     *
     * @return int[]
     */
    public function getRelatedProductsIds(Product $product)
    {
        return $this->getRelatedItemsIds(
            $product,
            $this->container->get('oro_product.related_item.related_product.finder_strategy')
        );
    }

    /**
     * @param string $productType
     *
     * @return bool
     */
    public function isConfigurableType($productType)
    {
        return $productType === Product::TYPE_CONFIGURABLE;
    }

    /**
     * @param bool $numericalOnly
     * @param bool $withRelations
     *
     * @return array
     */
    public function getAutocompleteData($numericalOnly = false, $withRelations = true)
    {
        return $this->getAutocompleteFieldsProvider()->getAutocompleteData($numericalOnly, $withRelations);
    }

    /**
     * @param Product $product
     *
     * @return int[]
     */
    public function getUpsellProductsIds(Product $product)
    {
        return $this->getRelatedItemsIds(
            $product,
            $this->container->get('oro_product.related_item.upsell_product.finder_strategy')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param Product $product
     * @param FinderStrategyInterface $finderStrategy
     * @return \int[]
     */
    private function getRelatedItemsIds(Product $product, FinderStrategyInterface $finderStrategy)
    {
        return $finderStrategy->findIds($product, false);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_product.autocomplete_fields_provider' => AutocompleteFieldsProvider::class,
            'oro_product.related_item.related_product.finder_strategy' => RelatedProductFinderDatabaseStrategy::class,
            'oro_product.related_item.upsell_product.finder_strategy' => UpsellProductFinderDatabaseStrategy::class,
        ];
    }
}

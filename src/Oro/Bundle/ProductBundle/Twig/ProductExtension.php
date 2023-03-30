<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions related to products:
 *   - oro_product_expression_autocomplete_data
 *   - is_configurable_product_type
 *   - is_kit_product_type
 *   - get_upsell_products_ids
 *   - get_related_products_ids
 *   - get_related_items_translation_key
 */
class ProductExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_product_expression_autocomplete_data', [$this, 'getAutocompleteData']),
            new TwigFunction('is_configurable_product_type', [$this, 'isConfigurableType']),
            new TwigFunction('is_kit_product_type', [$this, 'isKitType']),
            new TwigFunction('get_upsell_products_ids', [$this, 'getUpsellProductsIds']),
            new TwigFunction('get_related_products_ids', [$this, 'getRelatedProductsIds']),
            new TwigFunction('get_related_items_translation_key', [$this, 'getRelatedItemsTranslationKey']),
        ];
    }

    /**
     * @param Product $product
     *
     * @return int[]
     */
    public function getRelatedProductsIds(Product $product)
    {
        return $this->getRelatedItemsIds($product, $this->getRelatedProductFinderStrategy());
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

    public function isKitType(?string $productType): bool
    {
        return $productType === Product::TYPE_KIT;
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
        return $this->getRelatedItemsIds($product, $this->getUpsellProductFinderStrategy());
    }

    /**
     * @param Product $product
     * @param FinderStrategyInterface $finderStrategy
     * @return int[]
     */
    private function getRelatedItemsIds(Product $product, FinderStrategyInterface $finderStrategy)
    {
        return $finderStrategy->findIds($product, false);
    }

    /**
     * @return string
     */
    public function getRelatedItemsTranslationKey()
    {
        return $this->getRelatedItemConfigHelper()->getRelatedItemsTranslationKey();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_product.autocomplete_fields_provider' => AutocompleteFieldsProvider::class,
            'oro_product.related_item.related_product.finder_strategy' => FinderStrategyInterface::class,
            'oro_product.related_item.upsell_product.finder_strategy' => FinderStrategyInterface::class,
            'oro_product.related_item.helper.config_helper' => RelatedItemConfigHelper::class,
        ];
    }

    private function getAutocompleteFieldsProvider(): AutocompleteFieldsProvider
    {
        return $this->container->get('oro_product.autocomplete_fields_provider');
    }

    private function getRelatedProductFinderStrategy(): FinderStrategyInterface
    {
        return $this->container->get('oro_product.related_item.related_product.finder_strategy');
    }

    private function getUpsellProductFinderStrategy(): FinderStrategyInterface
    {
        return $this->container->get('oro_product.related_item.upsell_product.finder_strategy');
    }

    private function getRelatedItemConfigHelper(): RelatedItemConfigHelper
    {
        return $this->container->get('oro_product.related_item.helper.config_helper');
    }
}

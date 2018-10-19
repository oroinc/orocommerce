<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormView;

/**
 * Introduces function to get products' related/upsell items ids, check if product type is configurable, generate
 * form id for line items form and get data for rendering autocomplete input.
 */
class ProductExtension extends \Twig_Extension
{
    const NAME = 'oro_product';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
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
            new \Twig_SimpleFunction(
                'is_configurable_product_type',
                [$this, 'isConfigurableType']
            ),
            new \Twig_SimpleFunction(
                'get_upsell_products_ids',
                [$this, 'getUpsellProductsIds']
            ),
            new \Twig_SimpleFunction(
                'get_related_products_ids',
                [$this, 'getRelatedProductsIds']
            ),
            new \Twig_SimpleFunction(
                'set_unique_line_item_form_id',
                [$this, 'setUniqueLineItemFormId']
            ),
        ];
    }

    /**
     * @param FormView $form
     * @param Product|array $product
     */
    public function setUniqueLineItemFormId($form, $product = [])
    {
        if (!isset($form->vars['_notUniqueId'])) {
            $form->vars['_notUniqueId'] = $form->vars['id'];
        }

        $productId = null;
        if ($product) {
            $productId  = is_array($product) ? $product['id'] : $product->getId();
        }
        if ($productId) {
            $form->vars['id'] = sprintf('%s-product-id-%s', $form->vars['_notUniqueId'], $productId);
        } else {
            $form->vars['id'] = $form->vars['_notUniqueId'];
        }

        $form->vars['attr']['id'] = $form->vars['id'];
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
}

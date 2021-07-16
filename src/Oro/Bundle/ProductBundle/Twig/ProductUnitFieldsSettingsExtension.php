<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to retrieve product unit display configuration for a product:
 *   - oro_is_product_unit_selection_visible
 *   - oro_is_product_primary_unit_visible
 *   - oro_is_adding_additional_units_to_product_available
 */
class ProductUnitFieldsSettingsExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ProductUnitFieldsSettingsInterface
     */
    protected function getProductUnitFieldsSettings()
    {
        return $this->container->get('oro_product.visibility.product_unit_fields_settings');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_is_product_unit_selection_visible',
                [$this, 'isProductUnitSelectionVisible']
            ),
            new TwigFunction(
                'oro_is_product_primary_unit_visible',
                [$this, 'isProductPrimaryUnitVisible']
            ),
            new TwigFunction(
                'oro_is_adding_additional_units_to_product_available',
                [$this, 'isAddingAdditionalUnitsToProductAvailable']
            ),
        ];
    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    public function isProductUnitSelectionVisible(Product $product)
    {
        return $this->getProductUnitFieldsSettings()->isProductUnitSelectionVisible($product);
    }

    /**
     * @param Product|null $product
     *
     * @return bool
     */
    public function isProductPrimaryUnitVisible(Product $product = null)
    {
        return $this->getProductUnitFieldsSettings()->isProductPrimaryUnitVisible($product);
    }

    /**
     * @param Product|null $product
     *
     * @return bool
     */
    public function isAddingAdditionalUnitsToProductAvailable(Product $product = null)
    {
        return $this->getProductUnitFieldsSettings()->isAddingAdditionalUnitsToProductAvailable($product);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_product.visibility.product_unit_fields_settings' => ProductUnitFieldsSettingsInterface::class,
        ];
    }
}

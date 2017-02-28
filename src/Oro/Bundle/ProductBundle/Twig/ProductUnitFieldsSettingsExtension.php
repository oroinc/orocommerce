<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;

class ProductUnitFieldsSettingsExtension extends \Twig_Extension
{
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
            new \Twig_SimpleFunction(
                'oro_is_product_unit_selection_visible',
                [$this, 'isProductUnitSelectionVisible']
            ),
            new \Twig_SimpleFunction(
                'oro_is_product_primary_unit_visible',
                [$this, 'isProductPrimaryUnitVisible']
            ),
            new \Twig_SimpleFunction(
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
}

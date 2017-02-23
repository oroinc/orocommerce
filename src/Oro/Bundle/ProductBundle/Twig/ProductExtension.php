<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;

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
                'oro_product_expression_autocomplete_data',
                [$this, 'getAutocompleteData']
            ),
            new \Twig_SimpleFunction(
                'is_configurable_product_type',
                [$this, 'isConfigurableType']
            ),
        ];
    }

    /**
     * @param string $productType
     * @return bool
     */
    public function isConfigurableType($productType)
    {
        return $productType === Product::TYPE_CONFIGURABLE;
    }

    /**
     * @param bool $numericalOnly
     * @param bool $withRelations
     * @return array
     */
    public function getAutocompleteData($numericalOnly = false, $withRelations = true)
    {
        return $this->getAutocompleteFieldsProvider()->getAutocompleteData($numericalOnly, $withRelations);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

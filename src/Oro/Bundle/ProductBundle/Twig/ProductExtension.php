<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;

class ProductExtension extends \Twig_Extension
{
    const NAME = 'oro_product';

    /**
     * @var AutocompleteFieldsProvider
     */
    private $autocompleteFieldsProvider;

    /**
     * @param AutocompleteFieldsProvider $autocompleteFieldsProvider
     */
    public function __construct(AutocompleteFieldsProvider $autocompleteFieldsProvider)
    {
        $this->autocompleteFieldsProvider = $autocompleteFieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_product_expression_autocomplete_data',
                [$this->autocompleteFieldsProvider, 'getAutocompleteData']
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

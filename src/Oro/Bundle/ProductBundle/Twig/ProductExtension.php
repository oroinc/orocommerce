<?php

namespace Oro\Bundle\ProductBundle\Twig;

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
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

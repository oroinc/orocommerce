<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate product page template selections.
 *
 * This constraint ensures that the selected page template for a product is valid and compatible
 * with the configured route, preventing invalid template assignments.
 */
class ProductPageTemplate extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.entity.entity_field_fallback_value.invalid';

    /**
     * @var string
     */
    public $route;

    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->route = $options['route'] ?? null;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return ProductPageTemplateValidator::ALIAS;
    }
}

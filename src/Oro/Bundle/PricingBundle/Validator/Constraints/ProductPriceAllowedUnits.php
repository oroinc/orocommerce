<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Import validation
 */
class ProductPriceAllowedUnits extends Constraint
{
    /**
     * @var string
     */
    public $notExistingProductMessage = 'oro.pricing.validators.product_price.not_existing_product.message';

    /**
     * @var string
     */
    public $notExistingUnitMessage = 'oro.pricing.validators.product_price.not_existing_unit.message';

    /**
     * @var string
     */
    public $notAllowedUnitMessage = 'oro.pricing.validators.product_price.not_allowed_unit.message';

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_pricing_product_price_allowed_units_validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}

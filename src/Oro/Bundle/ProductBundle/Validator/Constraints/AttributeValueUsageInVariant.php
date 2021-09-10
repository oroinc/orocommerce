<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ConfigModelAwareConstraintInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Checks that select attribute value are not removed if were used as product variant values.
 */
class AttributeValueUsageInVariant extends Constraint implements ConfigModelAwareConstraintInterface
{
    public $message = 'oro.product.attribute_value.used_in_product_variant_field.message';

    /**
     * @var ConfigModel|null
     */
    public $configModel;

    public function validatedBy()
    {
        return AttributeValueUsageInVariantValidator::ALIAS;
    }

    public function getConfigModel(): ConfigModel
    {
        return $this->configModel;
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotEmptyConfigurableAttributesValidator extends ConstraintValidator
{
    const ALIAS = 'not_empty_configurable_attributes';

    /** @var VariantFieldProvider */
    private $provider;

    /**
     * @param VariantFieldProvider $provider
     */
    public function __construct(VariantFieldProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param string $value
     * @param Constraint|ProductBySku $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Product) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity must be instance of "%s", "%s" given',
                    Product::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if ($value->isConfigurable()) {
            $attributeFamily = $value->getAttributeFamily();
            if (!$this->provider->getVariantFields($attributeFamily)) {
                $this->context->addViolation($constraint->message, [
                    '%attributeFamily%' => $attributeFamily->getCode(),
                ]);
            }
        }
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint checking that only a product of type {@see \Oro\Bundle\ProductBundle\Entity\Product::TYPE_KIT}
 * can have kitItems.
 */
class OnlyProductKitCanHaveKitItems extends Constraint
{
    public const MUST_BE_PRODUCT_KIT = '6708edb5-0a93-42da-a73d-67ce712e1572';

    protected static $errorNames = [self::MUST_BE_PRODUCT_KIT => 'MUST_BE_PRODUCT_KIT'];

    public string $message = 'oro.product.validators.only_product_kit_can_have_kit_items.message';

    /**
     * @var bool Always initialize Product::$kitItems collection before checking if it is empty. Leave it as "false"
     *  if you don't have to check the already existing product that might have changed its type somehow (normally it
     *  should not be possible). Switching to "true" may cause extra queries initializing
     *  Product::$kitItems collection for every non-kit product.
     */
    public bool $forceInitialize = false;

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}

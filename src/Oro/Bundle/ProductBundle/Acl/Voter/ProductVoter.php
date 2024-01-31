<?php

namespace Oro\Bundle\ProductBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByProductProvider;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Prevents removal of simple products that relate to Kit products.
 */
class ProductVoter extends AbstractEntityVoter
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::DELETE];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        private ProductKitsByProductProvider $productKitsByProductProvider
    ) {
        parent::__construct($doctrineHelper);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference($class, $identifier);
        if (!$product->isSimple()) {
            return self::ACCESS_ABSTAIN;
        }

        $skus = $this->productKitsByProductProvider->getRelatedProductKitsSku($product);

        return empty($skus) ? self::ACCESS_ABSTAIN : self::ACCESS_DENIED;
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Prevents direct access to the pages of disabled products on the storefront.
 */
class ProductStatusVoter extends AbstractEntityVoter
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::VIEW];

    private FrontendHelper $frontendHelper;

    public function __construct(DoctrineHelper $doctrineHelper, FrontendHelper $frontendHelper)
    {
        parent::__construct($doctrineHelper);
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            return parent::vote($token, $object, $attributes);
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var Product|null $product */
        $product = $this->doctrineHelper->getEntityRepository($class)->find($identifier);
        if (null === $product) {
            return self::ACCESS_ABSTAIN;
        }

        return $product->getStatus() === Product::STATUS_ENABLED
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }
}

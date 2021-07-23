<?php

namespace Oro\Bundle\ProductBundle\Acl\Voter;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Prevents direct access to the pages of disabled products on the storefront.
 */
class ProductStatusVoter extends AbstractEntityVoter
{
    /**
     * @var array
     */
    protected $supportedAttributes = [BasicPermission::VIEW];

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if ($this->frontendHelper && $this->frontendHelper->isFrontendRequest()) {
            return parent::vote($token, $object, $attributes);
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var $repository ProductRepository */
        $repository = $this->doctrineHelper->getEntityRepository($class);

        /** @var Product $product */
        $product = $repository->find($identifier);

        if (!$product) {
            return self::ACCESS_ABSTAIN;
        }

        return $product->getStatus() === Product::STATUS_ENABLED ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }

    public function setFrontendHelper(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }
}

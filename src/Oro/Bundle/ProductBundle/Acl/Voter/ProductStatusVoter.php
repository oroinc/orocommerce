<?php

namespace Oro\Bundle\ProductBundle\Acl\Voter;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Security voter that prevents direct access to the pages of disabled products on the front store
 */
class ProductStatusVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_VIEW = 'VIEW';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_VIEW,
    ];

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

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function setFrontendHelper(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }
}

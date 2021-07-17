<?php

namespace Oro\Bundle\VisibilityBundle\Acl\Voter;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Prevents direct access to the products with disabled visibility.
 */
class ProductVisibilityVoter extends AbstractEntityVoter
{
    /** @var array */
    protected $supportedAttributes = [BasicPermission::VIEW];

    /** @var FrontendHelper */
    private $frontendHelper;

    /** @var ResolvedProductVisibilityProvider|null */
    private $resolvedProductVisibilityProvider;

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        if ($this->frontendHelper && $this->frontendHelper->isFrontendRequest()) {
            return parent::vote($token, $object, $attributes);
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @inheritdoc
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        return $this->resolvedProductVisibilityProvider->isVisible($identifier)
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    public function setFrontendHelper(FrontendHelper $frontendHelper): void
    {
        $this->frontendHelper = $frontendHelper;
    }

    public function setResolvedProductVisibilityProvider(
        ?ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider
    ): void {
        $this->resolvedProductVisibilityProvider = $resolvedProductVisibilityProvider;
    }
}

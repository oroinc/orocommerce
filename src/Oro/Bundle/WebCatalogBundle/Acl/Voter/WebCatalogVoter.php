<?php

namespace Oro\Bundle\WebCatalogBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Prevents removal of web catalogs that are in use.
 */
class WebCatalogVoter extends AbstractEntityVoter
{
    /** @var array */
    protected $supportedAttributes = [BasicPermission::DELETE];

    /** @var WebCatalog */
    protected $object;

    /** @var WebCatalogUsageProviderInterface */
    protected $usageProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebCatalogUsageProviderInterface $usageProvider
    ) {
        parent::__construct($doctrineHelper);
        $this->usageProvider = $usageProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->object = $object;

        return parent::vote($token, $object, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->usageProvider->isInUse($this->object)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}

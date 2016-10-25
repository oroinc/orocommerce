<?php

namespace Oro\Bundle\WebCatalogBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WebCatalogVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'DELETE';

    /**
     * @var WebCatalog
     */
    protected $object;

    /**
     * @var WebCatalogUsageProvider
     */
    protected $usageProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WebCatalogUsageProvider $usageProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebCatalogUsageProvider $usageProvider
    ) {
        parent::__construct($doctrineHelper);

        $this->supportedAttributes = [self::ATTRIBUTE_DELETE];
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

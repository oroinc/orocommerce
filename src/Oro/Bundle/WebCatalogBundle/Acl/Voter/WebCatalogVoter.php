<?php

namespace Oro\Bundle\WebCatalogBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WebCatalogVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'DELETE';

    /**
     * @var WebCatalog
     */
    protected $object;

    /**
     * @var WebCatalogUsageProviderInterface
     */
    protected $usageProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WebCatalogUsageProviderInterface $usageProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebCatalogUsageProviderInterface $usageProvider
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

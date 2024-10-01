<?php

namespace Oro\Bundle\WebCatalogBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Prevents removal of web catalogs that are in use.
 */
class WebCatalogVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    protected $supportedAttributes = [BasicPermission::DELETE];

    private ContainerInterface $container;

    private mixed $object;

    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_web_catalog.provider.web_catalog_usage_provider' => WebCatalogUsageProviderInterface::class
        ];
    }

    #[\Override]
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        $this->object = $object;
        try {
            return parent::vote($token, $object, $attributes);
        } finally {
            $this->object = null;
        }
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return $this->getWebCatalogUsageProvider()->isInUse($this->object)
            ? self::ACCESS_DENIED
            : self::ACCESS_ABSTAIN;
    }

    private function getWebCatalogUsageProvider(): WebCatalogUsageProviderInterface
    {
        return $this->container->get('oro_web_catalog.provider.web_catalog_usage_provider');
    }
}

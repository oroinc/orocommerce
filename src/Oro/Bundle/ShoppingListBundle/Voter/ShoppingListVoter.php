<?php

namespace Oro\Bundle\ShoppingListBundle\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Denies access to shopping lists created on another websites.
 */
class ShoppingListVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [
        BasicPermission::VIEW,
        BasicPermission::CREATE,
        BasicPermission::EDIT,
        BasicPermission::DELETE
    ];

    private ContainerInterface $container;

    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_website.manager' => WebsiteManager::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        $currentWebsite = $this->getWebsiteManager()->getCurrentWebsite();
        if (null === $currentWebsite) {
            return self::ACCESS_ABSTAIN;
        }

        /** @var ShoppingList|null $shoppingList */
        $shoppingList = $this->doctrineHelper->getEntity($class, $identifier);
        if (null === $shoppingList || null === $shoppingList->getWebsite()) {
            return self::ACCESS_ABSTAIN;
        }

        return $shoppingList->getWebsite()->getId() === $currentWebsite->getId()
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    private function getWebsiteManager(): WebsiteManager
    {
        return $this->container->get('oro_website.manager');
    }
}

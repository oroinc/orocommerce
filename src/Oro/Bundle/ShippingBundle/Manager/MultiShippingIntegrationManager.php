<?php

namespace Oro\Bundle\ShippingBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShippingBundle\Entity\MultiShippingSettings;
use Oro\Bundle\ShippingBundle\Integration\MultiShippingChannelType;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Manager for creating Multi Shipping integration
 */
class MultiShippingIntegrationManager
{
    public const DEFAULT_MULTI_SHIPPING_INTEGRATION_NAME = 'Multi Shipping';

    private ManagerRegistry $doctrine;
    private TokenAccessorInterface $tokenAccessor;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        ManagerRegistry $doctrine,
        TokenAccessorInterface $tokenAccessor,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function createIntegration(): Channel
    {
        $channel = $this->findIntegration();

        if (!empty($channel)) {
            return $channel;
        }

        if (!$this->authorizationChecker->isGranted('oro_integration_create')) {
            throw new AccessDeniedException();
        }

        $transport = new MultiShippingSettings();

        $channel = new Channel();
        $channel->setType(MultiShippingChannelType::TYPE)
            ->setName(self::DEFAULT_MULTI_SHIPPING_INTEGRATION_NAME)
            ->setEnabled(true)
            ->setOrganization($this->getOrganization())
            ->setDefaultUserOwner($this->getMainUser())
            ->setTransport($transport);

        $manager = $this->doctrine->getManagerForClass(Channel::class);
        $manager->persist($channel);
        $manager->flush();

        return $channel;
    }

    public function integrationExists(): bool
    {
        return (bool)$this->findIntegration();
    }

    private function findIntegration(): ?Channel
    {
        return $this->doctrine->getRepository(Channel::class)
            ->findOneBy([
                'type' => MultiShippingChannelType::TYPE,
                'organization' => $this->getOrganization(),
            ]);
    }

    private function getOrganization(): Organization
    {
        return $this->tokenAccessor->getOrganization();
    }

    private function getMainUser(): User
    {
        $user = $this->tokenAccessor->getUser();

        if (!$user instanceof User) {
            throw new \InvalidArgumentException(
                sprintf(
                    'User must be an instance of "%s", "%s" is given.',
                    User::class,
                    is_object($user) ? ClassUtils::getClass($user) : gettype($user)
                )
            );
        }

        return $user;
    }
}

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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides functionality to create a new Multi Shipping integration or get existing one if any.
 */
class MultiShippingIntegrationManager
{
    private ManagerRegistry $doctrine;
    private TokenAccessorInterface $tokenAccessor;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TranslatorInterface $translator;

    public function __construct(
        ManagerRegistry $doctrine,
        TokenAccessorInterface $tokenAccessor,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
    }

    public function createIntegration(): Channel
    {
        $organization = $this->getOrganization();

        $channel = $this->findIntegration($organization);
        if (null !== $channel) {
            return $channel;
        }

        if (!$this->authorizationChecker->isGranted('oro_integration_create')) {
            throw new AccessDeniedException();
        }

        $channel = new Channel();
        $channel->setType(MultiShippingChannelType::TYPE);
        $channel->setName($this->translator->trans('oro.shipping.multi_shipping_method.label'));
        $channel->setEnabled(true);
        $channel->setOrganization($organization);
        $channel->setDefaultUserOwner($this->getUser());
        $channel->setTransport(new MultiShippingSettings());

        $manager = $this->doctrine->getManagerForClass(Channel::class);
        $manager->persist($channel);
        $manager->flush();

        return $channel;
    }

    public function integrationExists(): bool
    {
        return null !== $this->findIntegration($this->getOrganization());
    }

    private function findIntegration(Organization $organization): ?Channel
    {
        return $this->doctrine->getRepository(Channel::class)
            ->findOneBy(['type' => MultiShippingChannelType::TYPE, 'organization' => $organization]);
    }

    private function getOrganization(): Organization
    {
        $organization = $this->tokenAccessor->getOrganization();
        if (null === $organization) {
            throw new \RuntimeException('Organization must exist.');
        }

        return $organization;
    }

    private function getUser(): User
    {
        $user = $this->tokenAccessor->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException(sprintf(
                'User must be an instance of "%s", "%s" is given.',
                User::class,
                \is_object($user) ? ClassUtils::getClass($user) : \gettype($user)
            ));
        }

        return $user;
    }
}

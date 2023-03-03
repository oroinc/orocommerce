<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The service to load a specific type of integration channels.
 */
class ChannelLoader implements ChannelLoaderInterface
{
    private ManagerRegistry $doctrine;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        ManagerRegistry $doctrine,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->doctrine = $doctrine;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function loadChannels(string $channelType, bool $applyAcl, Organization $organization = null): array
    {
        $organization = $organization ?? $this->tokenAccessor->getOrganization();
        if (null === $organization || ($applyAcl && !$this->isViewChannelGranted())) {
            return [];
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Channel::class);
        $qb = $em->createQueryBuilder()
            ->from(Channel::class, 'channel')
            ->select('channel')
            ->where('channel.type = :type AND channel.organization = :organization')
            ->setParameter('type', $channelType)
            ->setParameter('organization', $organization);

        return $qb->getQuery()->getResult();
    }

    private function isViewChannelGranted(): bool
    {
        return $this->authorizationChecker->isGranted(
            BasicPermission::VIEW,
            new ObjectIdentity(EntityAclExtension::NAME, Channel::class)
        );
    }
}

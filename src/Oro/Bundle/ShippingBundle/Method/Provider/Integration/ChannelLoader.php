<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
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
    private AclHelper $aclHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        AclHelper $aclHelper
    ) {
        $this->doctrine = $doctrine;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->aclHelper = $aclHelper;
    }

    /**
     * Loads channels of the given type using one of four scoping strategies:
     *
     *  - ACL on, organization given: check VIEW (return empty if denied), then filter by the given org.
     *  - ACL on, organization null: check VIEW (return empty if denied), then let AclHelper resolve cross-org access
     *    via the token.
     *  - ACL off, organization given: just filter by the given org.
     *  - ACL off, organization null: filter by the token's org, or return empty if there is no token.
     */
    #[\Override]
    public function loadChannels(string $channelType, bool $applyAcl, ?Organization $organization = null): array
    {
        // Under a global org filtering by token org gives nothing, so we let AclHelper handle cross-org access.
        return $applyAcl
            ? $this->loadWithAcl($channelType, $organization)
            : $this->loadByOrganization($channelType, $organization ?? $this->tokenAccessor->getOrganization());
    }

    private function loadWithAcl(string $channelType, ?Organization $organization): array
    {
        if (!$this->isViewChannelGranted()) {
            return [];
        }

        if (null !== $organization) {
            return $this->loadByOrganization($channelType, $organization);
        }

        return $this->aclHelper->apply($this->createBaseQueryBuilder($channelType))->getResult();
    }

    private function loadByOrganization(string $channelType, ?Organization $organization): array
    {
        if (null === $organization) {
            return [];
        }

        return $this->createBaseQueryBuilder($channelType)
            ->andWhere('channel.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getResult();
    }

    private function createBaseQueryBuilder(string $channelType): QueryBuilder
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Channel::class);

        return $em->createQueryBuilder()
            ->from(Channel::class, 'channel')
            ->select('channel')
            ->where('channel.type = :type')
            ->setParameter('type', $channelType);
    }

    private function isViewChannelGranted(): bool
    {
        return $this->authorizationChecker->isGranted(
            BasicPermission::VIEW,
            new ObjectIdentity(EntityAclExtension::NAME, Channel::class)
        );
    }
}

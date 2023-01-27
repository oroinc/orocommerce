<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * The service to load a specific type of integration channels.
 */
class ChannelLoader implements ChannelLoaderInterface
{
    private ManagerRegistry $doctrine;
    private AclHelper $aclHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        AclHelper $aclHelper
    ) {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function loadChannels(string $channelType, bool $applyAcl): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Channel::class);
        $qb = $em->createQueryBuilder()
            ->from(Channel::class, 'channel')
            ->select('channel')
            ->where('channel.type = :type')
            ->setParameter('type', $channelType);

        if ($applyAcl) {
            return $this->aclHelper->apply($qb)->getResult();
        }

        return $qb->getQuery()->getResult();
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Entity\NotificationMessage;

class NotificationMessageRepository extends EntityRepository
{
    /**
     * @param string $channel
     * @param string $topic
     * @param null|string $receiverEntityFQCN
     * @param null|int $receiverEntityId
     */
    public function removeMessages($channel, $topic, $receiverEntityFQCN = null, $receiverEntityId = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $criteria = $qb->expr()->andX(
            $qb->expr()->eq('message.channel', ':channel'),
            $qb->expr()->eq('message.topic', ':topic')
        );
        $qb->setParameter('channel', $channel);
        $qb->setParameter('topic', $topic);

        if ($receiverEntityFQCN) {
            $criteria->add($qb->expr()->eq('message.receiverEntityFQCN', ':receiverEntityFQCN'));
            $qb->setParameter('receiverEntityFQCN', $receiverEntityFQCN);
            if ($receiverEntityId) {
                $criteria->add($qb->expr()->eq('message.receiverEntityId', ':receiverEntityId'));
                $qb->setParameter('receiverEntityId', $receiverEntityId);
            }
        }

        $qb->delete($this->getEntityName(), 'message')
            ->where($criteria)
            ->getQuery()
            ->execute();
    }

    /**
     * @param string|array $channel
     * @param null|string $receiverEntityFQCN
     * @param null|int $receiverEntityId
     * @param null|string $topic
     * @return NotificationMessage[]
     */
    public function receiveMessages($channel, $receiverEntityFQCN = null, $receiverEntityId = null, $topic = null)
    {
        $criteria = [
            'channel' => $channel,
            'resolved' => false
        ];
        if ($topic) {
            $criteria['topic'] = $topic;
        }
        if ($receiverEntityFQCN) {
            $criteria['receiverEntityFQCN'] = $receiverEntityFQCN;
            if ($receiverEntityId) {
                $criteria['receiverEntityId'] = $receiverEntityId;
            }
        }

        return $this->findBy($criteria);
    }
}

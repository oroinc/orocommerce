<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class SlugListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @param ManagerRegistry $registry
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(ManagerRegistry $registry, MessageProducerInterface $messageProducer)
    {
        $this->registry = $registry;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        foreach ($this->getUpdatedSlugs($unitOfWork) as $changedSlug) {
            $this->synchronizeRedirectScopes($changedSlug);
        }
    }

    /**
     * @param UnitOfWork $unitOfWork
     * @return array|Slug[]
     */
    protected function getUpdatedSlugs(UnitOfWork $unitOfWork)
    {
        $updatedSlugs = [];
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Slug) {
                $updatedSlugs[] = $entity;
            }
        }

        return $updatedSlugs;
    }

    /**
     * @param Slug $slug
     */
    protected function synchronizeRedirectScopes(Slug $slug)
    {
        $this->messageProducer->send(
            Topics::SYNC_SLUG_REDIRECTS,
            new Message(['slugId' => $slug->getId()])
        );
    }
}

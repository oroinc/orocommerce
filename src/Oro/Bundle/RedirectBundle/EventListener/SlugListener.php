<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\RedirectBundle\Async\Topic\SyncSlugRedirectsTopic;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends messages to the message queue to synchronize redirect scopes for all changed slugs.
 */
class SlugListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    private MessageProducerInterface $messageProducer;

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $changedSlugs = $this->getUpdatedSlugs($event->getEntityManager()->getUnitOfWork());
        foreach ($changedSlugs as $changedSlug) {
            $this->synchronizeRedirectScopes($changedSlug);
        }
    }

    /**
     * @param UnitOfWork $unitOfWork
     *
     * @return Slug[]
     */
    private function getUpdatedSlugs(UnitOfWork $unitOfWork): array
    {
        $updatedSlugs = [];
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Slug) {
                $updatedSlugs[] = $entity;
            }
        }

        return $updatedSlugs;
    }

    private function synchronizeRedirectScopes(Slug $slug): void
    {
        $this->messageProducer->send(SyncSlugRedirectsTopic::getName(), ['slugId' => $slug->getId()]);
    }
}

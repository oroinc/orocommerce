<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\RedirectBundle\Async\Topic\SyncSlugRedirectsTopic;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Sends messages to the message queue to synchronize redirect scopes for all changed slugs.
 */
class SlugListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    public function __construct(ManagerRegistry $registry, MessageProducerInterface $messageProducer)
    {
        $this->registry = $registry;
        $this->messageProducer = $messageProducer;
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

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

    protected function synchronizeRedirectScopes(Slug $slug)
    {
        $this->messageProducer->send(SyncSlugRedirectsTopic::getName(), ['slugId' => $slug->getId()]);
    }
}

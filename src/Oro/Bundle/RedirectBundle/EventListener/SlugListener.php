<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

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
        $this->messageProducer->send(
            Topics::SYNC_SLUG_REDIRECTS,
            new Message(['slugId' => $slug->getId()])
        );
    }
}

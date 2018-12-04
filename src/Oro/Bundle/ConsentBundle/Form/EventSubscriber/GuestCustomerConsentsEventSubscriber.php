<?php

namespace Oro\Bundle\ConsentBundle\Form\EventSubscriber;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Queue\DelayedConsentAcceptancePersistQueueInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Process changes in consents after the main form was submitted.
 * This event subscriber processes only the case when anonymous customer user creates the RFQ.
 */
class GuestCustomerConsentsEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var DelayedConsentAcceptancePersistQueueInterface[]
     */
    private $delayedPersistQueues = [];

    /**
     * @param DelayedConsentAcceptancePersistQueueInterface $delayedPersistQueue
     */
    public function addDelayedPersistQueue(DelayedConsentAcceptancePersistQueueInterface $delayedPersistQueue)
    {
        $this->delayedPersistQueues[] = $delayedPersistQueue;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [FormEvents::POST_SUBMIT => 'saveConsentAcceptances'];
    }

    /**
     * @param FormEvent $event
     */
    public function saveConsentAcceptances(FormEvent $event)
    {
        $formData = $event->getData();

        if (!is_object($formData)) {
            return;
        }

        $delayedPersistQueue = $this->findSupportedDelayedPersistQueue($formData);
        if (null === $delayedPersistQueue) {
            return;
        }

        if ($event->getForm()->has(CustomerConsentsType::TARGET_FIELDNAME) &&
            $event->getForm()->isValid()
        ) {
            /** @var ConsentAcceptance[] $consentAcceptances */
            $consentAcceptances = $event
                ->getForm()
                ->get(CustomerConsentsType::TARGET_FIELDNAME)
                ->getData();

            if (!is_array($consentAcceptances) || empty($consentAcceptances)) {
                return;
            }

            $delayedPersistQueue->addConsentAcceptances($formData, $consentAcceptances);
        }
    }

    /**
     * @param object|null $entity
     *
     * @return null|DelayedConsentAcceptancePersistQueueInterface
     */
    private function findSupportedDelayedPersistQueue($entity)
    {
        $supportedDelayedPersistQueue = null;
        foreach ($this->delayedPersistQueues as $delayedPersistQueues) {
            if ($delayedPersistQueues->isEntitySupported($entity)) {
                $supportedDelayedPersistQueue = $delayedPersistQueues;
                break;
            }
        }

        return $supportedDelayedPersistQueue;
    }
}

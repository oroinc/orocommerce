<?php

namespace Oro\Bundle\ConsentBundle\Form\EventSubscriber;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Handler\SaveConsentAcceptanceHandler;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handles saving accepted consents if we have CustomerUser instance fetched from form event
 * This class is used in CustomerEditConsentsExtension and FrontendRfqExtension
 *
 * This is part of Customer edit profile form and RFQ form
 */
class CustomerConsentsEventSubscriber implements EventSubscriberInterface
{
    /** @var CustomerUserExtractor */
    private $customerUserExtractor;

    /** @var SaveConsentAcceptanceHandler */
    private $saveConsentAcceptanceHandler;

    /**
     * @param CustomerUserExtractor $customerUserExtractor
     * @param SaveConsentAcceptanceHandler $saveConsentAcceptanceHandler
     */
    public function __construct(
        CustomerUserExtractor $customerUserExtractor,
        SaveConsentAcceptanceHandler $saveConsentAcceptanceHandler
    ) {
        $this->customerUserExtractor = $customerUserExtractor;
        $this->saveConsentAcceptanceHandler = $saveConsentAcceptanceHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        /**
         * Add event listener after validation listener
         */
        return [FormEvents::POST_SUBMIT => ['saveConsentAcceptances', -10]];
    }

    /**
     * @param FormEvent $event
     */
    public function saveConsentAcceptances(FormEvent $event)
    {
        $customerUser = $this->getCustomerUserByEvent($event);
        if ($customerUser instanceof CustomerUser &&
            $event->getForm()->has(CustomerConsentsType::TARGET_FIELDNAME) &&
            $event->getForm()->isValid()
        ) {
            /** @var ConsentAcceptance[] $consentAcceptances */
            $consentAcceptances = $event
                ->getForm()
                ->get(CustomerConsentsType::TARGET_FIELDNAME)
                ->getData();

            if (!is_array($consentAcceptances)) {
                return;
            }

            $this->saveConsentAcceptanceHandler->save($customerUser, $consentAcceptances);
        }
    }

    /**
     * @param FormEvent $event
     *
     * @return null|CustomerUser
     */
    private function getCustomerUserByEvent(FormEvent $event)
    {
        $customerUser = $event->getData();
        if ($customerUser instanceof CustomerUser) {
            return $customerUser;
        }

        return $this->customerUserExtractor->extract($customerUser);
    }
}

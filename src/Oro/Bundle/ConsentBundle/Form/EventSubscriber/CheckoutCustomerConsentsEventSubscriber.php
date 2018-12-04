<?php

namespace Oro\Bundle\ConsentBundle\Form\EventSubscriber;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Form\Type\CheckoutCustomerConsentsType;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Handler\SaveConsentAcceptanceHandler;
use Oro\Bundle\ConsentBundle\Storage\CustomerConsentAcceptancesStorageInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Registered in CustomerConsentTransitionType and is triggered on on POST_SUBMIT form event
 * Handles saving accepted consents depending on CustomerUser.
 * If it is Guest it will not persist accepted consents permanently (because on this step we have no saved CustomerUser
 * to assign accepted consents to it) but save to session storage. This data will be fetched on other workflow step.
 *
 * This class is a part of CheckoutWorkflow logic.
 */
class CheckoutCustomerConsentsEventSubscriber implements EventSubscriberInterface
{
    /** @var SaveConsentAcceptanceHandler */
    private $saveConsentAcceptanceHandler;

    /** @var CustomerConsentAcceptancesStorageInterface */
    private $storage;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var CustomerUserExtractor */
    private $customerUserExtractor;

    /**
     * @param SaveConsentAcceptanceHandler $saveConsentAcceptanceHandler
     * @param CustomerConsentAcceptancesStorageInterface $storage
     * @param TokenStorageInterface $tokenStorage
     * @param CustomerUserExtractor $customerUserExtractor
     */
    public function __construct(
        SaveConsentAcceptanceHandler $saveConsentAcceptanceHandler,
        CustomerConsentAcceptancesStorageInterface $storage,
        TokenStorageInterface $tokenStorage,
        CustomerUserExtractor $customerUserExtractor
    ) {
        $this->saveConsentAcceptanceHandler = $saveConsentAcceptanceHandler;
        $this->storage = $storage;
        $this->tokenStorage = $tokenStorage;
        $this->customerUserExtractor = $customerUserExtractor;
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
        if (!$event->getForm()->has(CustomerConsentsType::TARGET_FIELDNAME) || !$event->getForm()->isValid()) {
            return;
        }

        $customerConsentsField = $event->getForm()->get(CustomerConsentsType::TARGET_FIELDNAME);

        /** @var ConsentAcceptance[] $consentAcceptances */
        $consentAcceptances = $customerConsentsField->getData();
        if (!is_array($consentAcceptances) || !$consentAcceptances) {
            return;
        }

        $checkout = $customerConsentsField
            ->getConfig()
            ->getOption(CheckoutCustomerConsentsType::CHECKOUT_OPTION_NAME);

        $customerUser = null;
        if ($checkout instanceof Checkout) {
            $customerUser = $this->customerUserExtractor->extract($checkout);
        }

        /**
         * Save consents to the storage after the workflow step "Agreements"
         * This event subscriber processes only the case when anonymous customer user proceed to the checkout.
         */
        if ($customerUser instanceof CustomerUser) {
            $this->saveConsentAcceptanceHandler->save($customerUser, $consentAcceptances);
        } elseif ($this->isGuestCustomerUser()) {
            $this->storage->saveData($consentAcceptances);
        }
    }

    /**
     * @return bool
     */
    private function isGuestCustomerUser()
    {
        return $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken;
    }
}

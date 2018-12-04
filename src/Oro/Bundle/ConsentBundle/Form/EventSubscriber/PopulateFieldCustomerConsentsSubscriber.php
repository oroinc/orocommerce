<?php

namespace Oro\Bundle\ConsentBundle\Form\EventSubscriber;

use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Use to fill customerConsentType field with data from database
 */
class PopulateFieldCustomerConsentsSubscriber implements EventSubscriberInterface
{
    /** @var ConsentAcceptanceProvider */
    private $consentAcceptanceProvider;

    /** @var CustomerUserExtractor */
    private $customerUserExtractor;

    /**
     * @param ConsentAcceptanceProvider $consentAcceptanceProvider
     * @param CustomerUserExtractor $customerUserExtractor
     */
    public function __construct(
        ConsentAcceptanceProvider $consentAcceptanceProvider,
        CustomerUserExtractor $customerUserExtractor
    ) {
        $this->consentAcceptanceProvider = $consentAcceptanceProvider;
        $this->customerUserExtractor = $customerUserExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [FormEvents::POST_SET_DATA => 'populateField'];
    }

    /**
     * {@inheritdoc}
     */
    public function populateField(FormEvent $event)
    {
        $customerUser = $this->getCustomerUserByEvent($event);
        if ($customerUser instanceof CustomerUser &&
            $event->getForm()->has(CustomerConsentsType::TARGET_FIELDNAME)
        ) {
            $consentAcceptancesData = $this->consentAcceptanceProvider->getCustomerConsentAcceptances();
            $event->getForm()
                ->get(CustomerConsentsType::TARGET_FIELDNAME)
                ->setData($consentAcceptancesData);
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

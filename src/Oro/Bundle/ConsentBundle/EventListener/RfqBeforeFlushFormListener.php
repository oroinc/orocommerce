<?php

namespace Oro\Bundle\ConsentBundle\EventListener;

use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * Listens `Oro\Bundle\FormBundle\Form\Handler\FormHandler` for persisting `Oro\Bundle\RFPBundle\Entity\Request` after
 * related form submitted
 *
 * It fills accepted consents collection to GUEST CustomerUser because this entity created on persist
 * only and is not available on FORM_SUBMIT still
 */
class RfqBeforeFlushFormListener
{
    use FeatureCheckerHolderTrait;

    /**
     * @param AfterFormProcessEvent $event
     */
    public function beforeFlush(AfterFormProcessEvent $event)
    {
        // No actions if consents feature disabled
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $formData = $event->getData();

        if ($formData instanceof Request) {
            $customerUser = $formData->getCustomerUser();
            if ($customerUser && $customerUser->isGuest()) {
                $form = $event->getForm();
                $acceptedConsents = $form->get(ConsentAcceptanceType::TARGET_FIELDNAME)->getData();

                $customerUser->setAcceptedConsents($acceptedConsents);
            }
        }
    }
}

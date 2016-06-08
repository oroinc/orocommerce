<?php

namespace OroB2B\Bundle\RFPBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Model\UpdateHandler;

use OroB2B\Bundle\RFPBundle\Mailer\RequestRepresentativesNotifier;

class RequestUpdateHandler extends UpdateHandler
{
    /**
     * @var RequestRepresentativesNotifier
     */
    protected $representativesNotifier;

    /**
     * @param RequestRepresentativesNotifier $representativesNotifier
     */
    public function setRepresentativesNotifier(RequestRepresentativesNotifier $representativesNotifier)
    {
        $this->representativesNotifier = $representativesNotifier;
    }

    /**
     * {@inheritdoc}
     */
    protected function processSave(
        FormInterface $form,
        $entity,
        $saveAndStayRoute,
        $saveAndCloseRoute,
        $saveMessage,
        $resultCallback = null
    ) {
        $this->representativesNotifier->notifyRepresentatives($entity);

        return parent::processSave($form, $entity, $saveAndStayRoute, $saveAndCloseRoute, $saveMessage);
    }
}

<?php

namespace Oro\Bundle\RFPBundle\Form\Handler;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Mailer\RequestRepresentativesNotifier;
use Symfony\Component\Form\FormInterface;

class RequestUpdateHandler extends UpdateHandler
{
    /**
     * @var RequestRepresentativesNotifier
     */
    protected $representativesNotifier;

    public function setRepresentativesNotifier(RequestRepresentativesNotifier $representativesNotifier)
    {
        $this->representativesNotifier = $representativesNotifier;
    }

    /**
     * {@inheritdoc}
     *
     * @param Request $entity
     */
    protected function processSave(
        FormInterface $form,
        $entity,
        $saveAndStayRoute,
        $saveAndCloseRoute,
        $saveMessage,
        $resultCallback = null
    ) {
        $result = parent::processSave($form, $entity, $saveAndStayRoute, $saveAndCloseRoute, $saveMessage);

        $this->representativesNotifier->sendConfirmationEmail($entity);
        $this->representativesNotifier->notifyRepresentatives($entity);

        return $result;
    }
}

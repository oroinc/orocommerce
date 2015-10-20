<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

class ProductVisibilityPostSubmitListener extends AbstractVisibilityPostSubmitListener
{
    /**
     * @param AfterFormProcessEvent $event
     */
    public function onPostSubmit(AfterFormProcessEvent $event)
    {
        $form = $event->getForm();
        $targetEntity = $form->getData();

        foreach ($form as $visibilityForm) {
            if (!$visibilityForm->isValid() || !is_object($targetEntity) || !$targetEntity->getId()) {
                return;
            }

            $this->saveForm($visibilityForm);
        }

        $this->getEntityManager($targetEntity)->flush();
    }
}

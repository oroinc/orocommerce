<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

class CategoryVisibilityPostSubmitListener extends AbstractVisibilityPostSubmitListener
{
    /**
     * @param AfterFormProcessEvent $event
     */
    public function onPostSubmit(AfterFormProcessEvent $event)
    {
        $form = $event->getForm();

        $visibilityForm = $form->get($this->visibilityField);
        $targetEntity = $visibilityForm->getData();

        if (!$visibilityForm->isValid() || !is_object($targetEntity) || !$targetEntity->getId()) {
            return;
        }

        $this->saveForm($visibilityForm);

        $this->getEntityManager($targetEntity)->flush();
    }
}

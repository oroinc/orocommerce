<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

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

        $this->saveForm($visibilityForm, $targetEntity);

        $this->getEntityManager($targetEntity)->flush();
    }
}

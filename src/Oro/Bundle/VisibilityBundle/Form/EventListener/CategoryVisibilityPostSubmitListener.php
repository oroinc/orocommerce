<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;

/**
 * Saves Category visibility form data
 */
class CategoryVisibilityPostSubmitListener
{
    /**
     * @var VisibilityFormPostSubmitDataHandler
     */
    protected $dataHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(VisibilityFormPostSubmitDataHandler $dataHandler, ManagerRegistry $registry)
    {
        $this->dataHandler = $dataHandler;
        $this->registry = $registry;
    }

    public function onPostSubmit(AfterFormProcessEvent $event)
    {
        $form = $event->getForm();

        if (!$form->has(EntityVisibilityType::VISIBILITY)) {
            return;
        }

        $visibilityForm = $form->get(EntityVisibilityType::VISIBILITY);
        $targetEntity = $visibilityForm->getData();

        $this->dataHandler->saveForm($visibilityForm, $targetEntity);

        $this->registry->getManagerForClass(Category::class)->flush();
    }
}

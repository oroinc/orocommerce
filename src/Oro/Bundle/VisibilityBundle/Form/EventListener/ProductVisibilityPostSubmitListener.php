<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityPostSubmitListener
{
    /**
     * @var VisibilityFormPostSubmitDataHandler
     */
    protected $postSubmitDataHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(VisibilityFormPostSubmitDataHandler $postSubmitDataHandler, ManagerRegistry $registry)
    {
        $this->postSubmitDataHandler = $postSubmitDataHandler;
        $this->registry = $registry;
    }

    public function onPostSubmit(AfterFormProcessEvent $event)
    {
        $form = $event->getForm();
        $targetEntity = $form->getData();

        foreach ($form->all() as $visibilityForm) {
            $this->postSubmitDataHandler->saveForm($visibilityForm, $targetEntity);
        }

        $this->registry->getManagerForClass(Product::class)->flush();
    }
}

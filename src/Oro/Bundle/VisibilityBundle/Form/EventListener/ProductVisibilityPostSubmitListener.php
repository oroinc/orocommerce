<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
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

    /**
     * @param VisibilityFormPostSubmitDataHandler $postSubmitDataHandler
     * @param ManagerRegistry $registry
     */
    public function __construct(VisibilityFormPostSubmitDataHandler $postSubmitDataHandler, ManagerRegistry $registry)
    {
        $this->postSubmitDataHandler = $postSubmitDataHandler;
        $this->registry = $registry;
    }

    /**
     * @param AfterFormProcessEvent $event
     */
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

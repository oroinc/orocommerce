<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Persists product visibility form data to the database after successful form submission.
 *
 * This listener handles form post-submit events to save visibility settings for products across all
 * visibility levels (all customers, customer groups, individual customers) after the form has been
 * successfully processed and validated.
 */
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

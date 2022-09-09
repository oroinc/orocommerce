<?php

namespace Oro\Bundle\VisibilityBundle\Form\Handler;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The handler for visibility form.
 */
class VisibilityFormDataHandler implements FormHandlerInterface
{
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function process($entity, FormInterface $form, Request $request)
    {
        $form->setData($entity);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->eventDispatcher->dispatch(
                    new AfterFormProcessEvent($form, $entity),
                    'oro_product.product.edit'
                );

                return true;
            }
        }

        return false;
    }
}

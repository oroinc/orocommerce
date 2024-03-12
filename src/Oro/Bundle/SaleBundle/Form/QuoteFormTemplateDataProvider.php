<?php

namespace Oro\Bundle\SaleBundle\Form;

use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides data for TWIG on a create/update quote page.
 */
class QuoteFormTemplateDataProvider implements FormTemplateDataProviderInterface
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function getData($entity, FormInterface $form, Request $request)
    {
        if (!$entity instanceof Quote) {
            throw new \InvalidArgumentException(
                sprintf('`%s` supports only `%s` instance as form data (entity).', self::class, Quote::class)
            );
        }

        $submittedData = $request->get($form->getName());
        $event = new QuoteEvent($form, $form->getData(), $submittedData);
        $this->eventDispatcher->dispatch($event, QuoteEvent::NAME);
        $quoteData = $event->getData()->getArrayCopy();

        return [
            'entity' => $entity,
            'form' => $form->createView(),
            'quoteData' => $quoteData,
        ];
    }
}

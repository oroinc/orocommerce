<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds quotes grid to RFQ view page.
 */
class RequestQuotesViewPageListener
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function onBeforeViewRender(BeforeViewRenderEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Request) {
            return;
        }

        $quotesData = $event->getTwigEnvironment()->render(
            '@OroSale/Request/requestQuotes.html.twig',
            [
                'gridParams' => [
                    'request_id' => $entity->getId(),
                    'related_entity_class' => Request::class
                ]
            ]
        );

        $data = $event->getData();
        $data['dataBlocks'][] = [
            'title' => $this->translator->trans('oro.sale.quote.entity_plural_label'),
            'subblocks' => [['data' => [$quotesData]]]
        ];

        $event->setData($data);
    }
}

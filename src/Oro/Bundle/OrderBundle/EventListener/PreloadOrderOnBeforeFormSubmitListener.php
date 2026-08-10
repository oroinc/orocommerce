<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Preloads the unit precisions of the order line item products before the order form is submitted
 * to avoid a separate query per product.
 */
final class PreloadOrderOnBeforeFormSubmitListener
{
    private array $preloadingConfig = [
        'lineItems' => [
            'product' => [
                'unitPrecisions' => [],
            ],
        ],
    ];

    public function __construct(private readonly PreloadingManager $preloadingManager)
    {
    }

    public function setPreloadingConfig(array $preloadingConfig): void
    {
        $this->preloadingConfig = $preloadingConfig;
    }

    public function onBeforeFormSubmit(FormProcessEvent $event): void
    {
        $order = $event->getData();
        if (!$order instanceof Order) {
            // The event is dispatched for every form handled by a form handler, so non-order forms are skipped.
            return;
        }

        $this->preloadingManager->preloadInEntities([$order], $this->preloadingConfig);
    }
}

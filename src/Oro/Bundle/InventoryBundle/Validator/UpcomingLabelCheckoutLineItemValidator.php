<?php

namespace Oro\Bundle\InventoryBundle\Validator;

use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides upcoming notification message for checkout line item.
 */
class UpcomingLabelCheckoutLineItemValidator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var DateTimeFormatterInterface
     */
    private $dateFormatter;

    /**
     * @var UpcomingProductProvider
     */
    private $productUpcomingProvider;

    public function __construct(
        UpcomingProductProvider $productUpcomingProvider,
        TranslatorInterface $translator,
        DateTimeFormatterInterface $dateFormatter
    ) {
        $this->productUpcomingProvider = $productUpcomingProvider;
        $this->translator = $translator;
        $this->dateFormatter = $dateFormatter;
    }

    public function getMessageIfUpcoming(ProductLineItemInterface $lineItem): ?string
    {
        $product = $lineItem->getProduct();

        if ($product !== null && $this->productUpcomingProvider->isUpcoming($product)) {
            $availabilityDate = $this->productUpcomingProvider->getAvailabilityDate($product);
            if ($availabilityDate) {
                return $this->translator->trans(
                    'oro.inventory.is_upcoming.notification_with_date',
                    ['%date%' => $this->dateFormatter->formatDate($availabilityDate)]
                );
            }

            return $this->translator->trans('oro.inventory.is_upcoming.notification');
        }

        return null;
    }
}

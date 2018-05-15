<?php

namespace Oro\Bundle\InventoryBundle\Validator;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Symfony\Component\Translation\TranslatorInterface;

class UpcomingLabelCheckoutLineItemValidator
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DateTimeFormatter
     */
    protected $dateFormatter;

    /**
     * @var ProductUpcomingProvider
     */
    protected $productUpcomingProvider;

    /**
     * @param ProductUpcomingProvider $ProductUpcomingProvider
     * @param TranslatorInterface $translator
     * @param DateTimeFormatter $dateFormatter
     */
    public function __construct(
        ProductUpcomingProvider $ProductUpcomingProvider,
        TranslatorInterface $translator,
        DateTimeFormatter $dateFormatter
    ) {
        $this->productUpcomingProvider = $ProductUpcomingProvider;
        $this->translator = $translator;
        $this->dateFormatter = $dateFormatter;
    }

    /**
     * @param mixed $lineItem
     *
     * @return null|string
     */
    public function getMessageIfLineItemUpcoming(CheckoutLineItem $lineItem)
    {
        $product = $lineItem->getProduct();
        if ($this->productUpcomingProvider->isUpcoming($product)) {
            $availabilityDate = $this->productUpcomingProvider->getAvailabilityDate($product);
            if ($availabilityDate) {
                $message = $this->translator->trans('oro.inventory.is_upcoming.notification_with_date');
                return $message . $this->dateFormatter->formatDate($availabilityDate);
            }

            return $this->translator->trans('oro.inventory.is_upcoming.notification');
        }

        return null;
    }
}

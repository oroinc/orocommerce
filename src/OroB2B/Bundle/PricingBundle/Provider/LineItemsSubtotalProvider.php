<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\CurrencyBundle\Model\PriceAwareInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use OroB2B\Bundle\PricingBundle\Model\LineItemsAwareInterface;
use OroB2B\Bundle\PricingBundle\Model\LineItemsSubtotal;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class LineItemsSubtotalProvider
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var RoundingServiceInterface
     */
    protected $rounding;

    /**
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     */
    public function __construct(TranslatorInterface $translator, RoundingServiceInterface $rounding)
    {
        $this->translator = $translator;
        $this->rounding = $rounding;
    }

    /**
     * Get line items subtotal
     *
     * @param LineItemsAwareInterface $entity
     *
     * @return LineItemsSubtotal
     */
    public function getSubtotal(LineItemsAwareInterface $entity)
    {
        $subtotalAmount = 0.0;
        $subtotal = new LineItemsSubtotal();
        $subtotal->setLabel($this->translator->trans('Line items subtotal'));//todo translate

        /**
         * TODO: Need to define currency exchange logic. BB-124
         */
        if (!$entity instanceof CurrencyAwareInterface) {
            $baseCurrency = 'USD';
        } else {
            $baseCurrency = $entity->getCurrency();
        }

        foreach ($entity->getLineItems() as $lineItem) {
            if (!$lineItem instanceof PriceAwareInterface || !$lineItem->getPrice() instanceof Price) {
                continue;
            }
            $rowTotal = $lineItem->getPrice()->getValue();
            if ($lineItem instanceof PriceTypeAwareInterface &&
                $lineItem->getPriceType() === PriceTypeAwareInterface::PRICE_TYPE_UNIT
            ) {
                $rowTotal *= $lineItem->getQuantity();
            }
            if ($baseCurrency !== $lineItem->getPrice()->getCurrency()) {
                $rowTotal *= $this->getExchangeRate($lineItem->getPrice()->getCurrency(), $baseCurrency);
            }
            $subtotalAmount += $rowTotal;
        }

        $subtotal->setAmount(
            $this->rounding->round($subtotalAmount)
        );

        $subtotal->setCurrency($entity->getCurrency());

        return $subtotal;
    }

    public function getLineItemSubtotal()
    {

    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    protected function getExchangeRate($fromCurrency, $toCurrency)
    {
        return 1.0;
    }
}

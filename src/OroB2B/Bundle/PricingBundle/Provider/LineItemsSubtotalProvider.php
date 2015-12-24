<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\CurrencyBundle\Model\PriceAwareInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use OroB2B\Bundle\PricingBundle\Entity\QuantityAwareInterface;
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
        $subtotal->setLabel($this->translator->trans('orob2b.pricing.lineitem.subtotal.label'));

        $baseCurrency = $this->getBaseCurrency($entity);
        foreach ($entity->getLineItems() as $lineItem) {
            if (!$lineItem instanceof PriceAwareInterface || !$lineItem->getPrice() instanceof Price) {
                continue;
            }

            $subtotalAmount += $this->handleRowTotal($lineItem, $baseCurrency);
        }

        $subtotal->setAmount(
            $this->rounding->round($subtotalAmount)
        );
        $subtotal->setCurrency($baseCurrency);

        return $subtotal;
    }

    /**
     * @param PriceAwareInterface $lineItem
     * @param string $baseCurrency
     * @return float|int
     */
    protected function handleRowTotal(PriceAwareInterface $lineItem, $baseCurrency)
    {
        $rowTotal = $lineItem->getPrice()->getValue();
        $rowCurrency = $lineItem->getPrice()->getCurrency();

        if ($lineItem instanceof PriceTypeAwareInterface &&
            $lineItem instanceof QuantityAwareInterface &&
            (int)$lineItem->getPriceType() === PriceTypeAwareInterface::PRICE_TYPE_UNIT
        ) {
            $rowTotal *= $lineItem->getQuantity();
        }

        if ($baseCurrency !== $rowCurrency) {
            $rowTotal *= $this->getExchangeRate($rowCurrency, $baseCurrency);
        }

        return $rowTotal;
    }

    /**
     * @param $entity
     * @return string
     */
    protected function getBaseCurrency($entity)
    {
        if (!$entity instanceof CurrencyAwareInterface) {
            return 'USD';
        } else {
            return $entity->getCurrency();
        }
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    protected function getExchangeRate($fromCurrency, $toCurrency)
    {
        /**
         * TODO: Need to define currency exchange logic. BB-124
         */
        return 1.0;
    }
}

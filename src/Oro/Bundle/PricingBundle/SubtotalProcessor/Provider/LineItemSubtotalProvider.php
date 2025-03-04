<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalCacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Component\Math\BigDecimal;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for line items with prices. SUM(ROUND(price*qty))
 */
class LineItemSubtotalProvider extends AbstractSubtotalProvider implements
    SubtotalProviderInterface,
    SubtotalCacheAwareInterface
{
    const TYPE = 'subtotal';
    const LABEL = 'oro.pricing.subtotals.subtotal.label';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        SubtotalProviderConstructorArguments $arguments
    ) {
        parent::__construct($arguments);
        $this->translator = $translator;
        $this->rounding = $rounding;
    }

    #[\Override]
    public function isSupported($entity)
    {
        return $entity instanceof LineItemsAwareInterface;
    }

    /**
     * Get line items subtotal
     *
     * @param LineItemsAwareInterface $entity
     *
     * @return Subtotal
     */
    #[\Override]
    public function getSubtotal($entity)
    {
        $amount = $this->isSupported($entity)
            ? $this->getRecalculatedSubtotalAmount($entity)
            : 0.0;

        return $this->createSubtotal($entity, $amount);
    }

    #[\Override]
    public function getCachedSubtotal(SubtotalAwareInterface $entity)
    {
        return $this->createSubtotal($entity, $entity->getSubtotal());
    }

    /**
     * @param object $entity
     * @param float $amount
     * @return Subtotal
     */
    protected function createSubtotal($entity, $amount)
    {
        $subtotal = new Subtotal();
        $subtotal->setLabel($this->translator->trans(self::LABEL));
        $subtotal->setType(self::TYPE);
        $subtotal->setVisible($amount > 0);
        $subtotal->setAmount($amount);
        $subtotal->setCurrency($this->getBaseCurrency($entity));

        return $subtotal;
    }

    /**
     * @param LineItemsAwareInterface $entity
     * @return float
     */
    protected function getRecalculatedSubtotalAmount($entity)
    {
        $subtotalAmount = BigDecimal::of(0);
        $baseCurrency = $this->getBaseCurrency($entity);
        foreach ($entity->getLineItems() as $lineItem) {
            if ($lineItem instanceof PriceAwareInterface && $lineItem->getPrice() instanceof Price) {
                $subtotalAmount = $subtotalAmount->plus($this->getRowTotal($lineItem, $baseCurrency));
            }
        }

        return $subtotalAmount->toFloat();
    }

    /**
     * @param PriceAwareInterface $lineItem
     * @param string $baseCurrency
     * @return float|int
     */
    public function getRowTotal(PriceAwareInterface $lineItem, $baseCurrency)
    {
        if (!$lineItem->getPrice()) {
            return 0;
        }

        $rowTotal = $lineItem->getPrice()->getValue() ?? 0.0;
        $rowCurrency = $lineItem->getPrice()->getCurrency();

        if ($lineItem instanceof QuantityAwareInterface) {
            $rowTotal = BigDecimal::of($rowTotal)
                ->multipliedBy((float)$lineItem->getQuantity())
                ->toFloat();
        }

        if ($baseCurrency !== $rowCurrency) {
            $rowTotal = BigDecimal::of($rowTotal)
                ->multipliedBy($this->getExchangeRate($rowCurrency, $baseCurrency))
                ->toFloat();
        }

        return $this->rounding->round($rowTotal);
    }
}

<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use OroB2B\Bundle\PricingBundle\Entity\QuantityAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class LineItemSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'subtotal';
    const NAME = 'orob2b.pricing.subtotals.subtotal';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /**
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     */
    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding
    ) {
        $this->translator = $translator;
        $this->rounding = $rounding;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
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
    public function getSubtotal($entity)
    {
        $subtotalAmount = 0.0;
        $subtotal = new Subtotal();
        $subtotal->setLabel($this->translator->trans(self::NAME . '.label'));
        $subtotal->setVisible(false);
        $subtotal->setType(self::TYPE);

        $baseCurrency = $this->getBaseCurrency($entity);
        foreach ($entity->getLineItems() as $lineItem) {
            if ($lineItem instanceof PriceAwareInterface && $lineItem->getPrice() instanceof Price) {
                $subtotalAmount += $this->getRowTotal($lineItem, $baseCurrency);
                $subtotal->setVisible(true);
            }
        }

        $subtotal->setAmount($this->rounding->round($subtotalAmount));
        $subtotal->setCurrency($baseCurrency);

        return $subtotal;
    }

    /**
     * @param PriceAwareInterface $lineItem
     * @param string $baseCurrency
     * @return float|int
     */
    protected function getRowTotal(PriceAwareInterface $lineItem, $baseCurrency)
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
}

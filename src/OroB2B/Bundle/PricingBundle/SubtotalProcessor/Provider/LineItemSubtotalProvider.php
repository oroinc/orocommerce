<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use OroB2B\Bundle\ProductBundle\Model\QuantityAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalCacheAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

class LineItemSubtotalProvider extends AbstractSubtotalProvider implements
    SubtotalProviderInterface,
    SubtotalCacheAwareInterface
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
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        UserCurrencyManager $currencyManager
    ) {
        parent::__construct($currencyManager);
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
        $amount = $this->isSupported($entity)
            ? $this->getRecalculatedSubtotalAmount($entity)
            : 0.0;

        return $this->createSubtotal($entity, $amount);
    }

    /**
     * {@inheritdoc}
     */
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
        $subtotal->setLabel($this->translator->trans(self::NAME . '.label'));
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
        $subtotalAmount = 0.0;
        $baseCurrency   = $this->getBaseCurrency($entity);
        foreach ($entity->getLineItems() as $lineItem) {
            if ($lineItem instanceof PriceAwareInterface && $lineItem->getPrice() instanceof Price) {
                $subtotalAmount += $this->getRowTotal($lineItem, $baseCurrency);
            }
        }

        return $this->rounding->round($subtotalAmount);
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

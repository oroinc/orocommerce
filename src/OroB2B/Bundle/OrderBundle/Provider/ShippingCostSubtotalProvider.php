<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;

use OroB2B\Bundle\OrderBundle\Model\ShippingAwareInterface;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

class ShippingCostSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'shipping_cost';
    const NAME = 'orob2b_order.subtotal_shipping_cost';
    const CURRENCY_DEFAULT = 'USD';

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
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param ShippingAwareInterface $entity
     * @return Subtotal
     */
    public function getSubtotal($entity)
    {
        if (!$this->isSupported($entity)) {
            throw new \InvalidArgumentException('Entity not supported for provider');
        }
        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $translation = 'orob2b.order.subtotals.' . self::TYPE;
        $subtotal->setLabel($this->translator->trans($translation));
        $subtotal->setVisible(false);

        if ($entity->getShippingCost() !== null) {
            $subtotalAmount = $entity->getShippingCost()->getValue();
            $subtotal->setAmount($this->rounding->round($subtotalAmount))
                ->setCurrency($this->getBaseCurrency($entity))
                ->setVisible(true);
        }

        return $subtotal;
    }

    /**
     * @param $entity
     * @return string
     */
    protected function getBaseCurrency($entity)
    {
        if (!$entity instanceof CurrencyAwareInterface) {
            return self::CURRENCY_DEFAULT;
        } else {
            return $entity->getCurrency();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $entity instanceof ShippingAwareInterface;
    }
}

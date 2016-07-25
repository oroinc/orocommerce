<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Model\ShippingAwareInterface;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

class ShippingCostSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'shipping_cost';
    const NAME = 'orob2b_order.subtotal_shipping_cost';

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
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $entity instanceof ShippingAwareInterface;
    }
}

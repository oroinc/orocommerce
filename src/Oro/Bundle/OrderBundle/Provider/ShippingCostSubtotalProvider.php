<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Subtotal provider for shipping. ROUND(shippingValue)
 */
class ShippingCostSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'shipping_cost';
    const NAME = 'oro_order.subtotal_shipping_cost';
    const SUBTOTAL_SORT_ORDER = 200;

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
     * @param SubtotalProviderConstructorArguments $arguments
     */
    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        SubtotalProviderConstructorArguments $arguments
    ) {
        parent::__construct($arguments);

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
        $subtotal->setSortOrder(self::SUBTOTAL_SORT_ORDER);
        $translation = 'oro.order.subtotals.' . self::TYPE;
        $subtotal->setLabel($this->translator->trans($translation));
        $subtotal->setVisible((bool) $entity->getShippingCost());
        $subtotal->setCurrency($this->getBaseCurrency($entity));

        $subtotalAmount = 0.0;
        if ($entity->getShippingCost() !== null) {
            $subtotalAmount = $entity->getShippingCost()->getValue();
        }
        $subtotal->setAmount($this->rounding->round($subtotalAmount));

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

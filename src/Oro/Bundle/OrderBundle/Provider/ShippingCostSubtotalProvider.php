<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for shipping. ROUND(shippingValue)
 */
class ShippingCostSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    public const TYPE = 'shipping_cost';
    public const SUBTOTAL_SORT_ORDER = 200;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var RoundingServiceInterface
     */
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

    /**
     * @param ShippingAwareInterface $entity
     * @return Subtotal
     */
    #[\Override]
    public function getSubtotal($entity)
    {
        if (!$this->isSupported($entity)) {
            throw new \InvalidArgumentException('Entity not supported for provider');
        }

        $subtotal = new Subtotal();
        $subtotal->setType(self::TYPE);
        $subtotal->setSortOrder(self::SUBTOTAL_SORT_ORDER);
        $subtotal->setLabel($this->translator->trans('oro.order.subtotals.' . self::TYPE));
        $subtotal->setRemovable(false);

        $shippingCost = $entity->getShippingCost();
        if (null === $shippingCost) {
            $subtotal->setVisible(false);
            $subtotal->setAmount(0.0);
            $subtotal->setCurrency($this->getBaseCurrency($entity));
        } else {
            $subtotal->setVisible(true);
            $subtotal->setAmount($this->rounding->round($shippingCost->getValue()));
            $subtotal->setCurrency($shippingCost->getCurrency() ?? $this->getBaseCurrency($entity));
        }

        return $subtotal;
    }

    #[\Override]
    public function isSupported($entity)
    {
        return $entity instanceof ShippingAwareInterface;
    }
}

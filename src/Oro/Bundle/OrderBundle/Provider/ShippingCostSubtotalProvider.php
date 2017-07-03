<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

class ShippingCostSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'shipping_cost';
    const NAME = 'oro_order.subtotal_shipping_cost';

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
        $translation = 'oro.order.subtotals.' . self::TYPE;
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

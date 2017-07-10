<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AppliedDiscountSubtotalProvider implements SubtotalProviderInterface
{
    const NAME = 'oro_promotion.subtotal_applied_discount';
    const TYPE = 'applied_discount';

    /**
     * @var OrdersAppliedDiscountsProvider
     */
    protected $discountsProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param OrdersAppliedDiscountsProvider $discountsProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(OrdersAppliedDiscountsProvider $discountsProvider, TranslatorInterface $translator)
    {
        $this->discountsProvider = $discountsProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     * @param Order $entity
     */
    public function getSubtotal($entity)
    {
        $label = $this->translator->trans('oro.promotion.discount.subtotal.order.label');
        $amount = $this->discountsProvider->getDiscountsAmountByOrder($entity);

        $subtotal = new Subtotal();
        $subtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);
        $subtotal->setType(self::TYPE);
        $subtotal->setLabel($label);
        $subtotal->setVisible($amount > 0.0);
        $subtotal->setCurrency($entity->getCurrency());
        $subtotal->setAmount($amount);

        return $subtotal;
    }

    /**
     * {@inheritDoc}
     */
    public function isSupported($entity)
    {
        if (!$entity instanceof Order) {
            return false;
        }

        if (!$entity->getId()) {
            return false;
        }

        return true;
    }
}

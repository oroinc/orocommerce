<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Symfony\Component\Translation\TranslatorInterface;

class AppliedDiscountSubtotalProvider implements SubtotalProviderInterface
{
    const NAME = 'oro_promotion.subtotal_discount';
    const TYPE = 'discount';

    /**
     * @var OrdersAppliedDiscountsProvider
     */
    protected $discountsProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * AppliedDiscountSubtotalProvider constructor.
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
        $subtotal = new Subtotal();
        $subtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);
        $subtotal->setType(self::TYPE);
        $subtotal->setLabel($this->translator->trans('oro.promotion_discount.order.label'));
        $subtotal->setVisible(true);
        $subtotal->setCurrency($entity->getCurrency());
        $subtotal->setAmount($this->discountsProvider->getOrderDiscountAmount($entity->getId()));
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
        if (!$this->discountsProvider->getOrderDiscounts($entity->getId())) {
            return false;
        }
        return true;
    }
}

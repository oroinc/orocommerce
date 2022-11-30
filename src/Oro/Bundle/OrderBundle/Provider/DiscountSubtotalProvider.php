<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Model\DiscountAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for discounts.
 */
class DiscountSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'discount';
    const SUBTOTAL_SORT_ORDER = 50;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var RoundingServiceInterface */
    protected $lineItemSubtotal;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        LineItemSubtotalProvider $lineItemSubtotal,
        TokenAccessorInterface $tokenAccessor,
        SubtotalProviderConstructorArguments $arguments
    ) {
        parent::__construct($arguments);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->lineItemSubtotal = $lineItemSubtotal;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param DiscountAwareInterface|LineItemsAwareInterface $entity
     *
     * @return Subtotal[]
     */
    public function getSubtotal($entity)
    {
        $subtotals = [];

        $discounts = $entity->getDiscounts();
        foreach ($discounts as $discount) {
            $subtotal = new Subtotal();

            $subtotal->setType(self::TYPE);
            $subtotal->setSortOrder(self::SUBTOTAL_SORT_ORDER);
            $description = $discount->getDescription();
            $title = $this->translator->trans('oro.order.subtotals.' . self::TYPE);
            if ($description) {
                if ($this->isFrontendUser()) {
                    $title = $description;
                } else {
                    $title = $description . ' (' . $title . ')';
                }
            }
            $subtotal->setLabel($title);
            $subtotal->setVisible(true);
            $subtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);
            $subtotal->setRemovable(false);

            $subtotalAmount = $this->getSubtotalAmount($discount, $entity);

            $subtotal->setAmount($this->rounding->round($subtotalAmount));
            $subtotal->setCurrency($this->getBaseCurrency($entity));
            $subtotals[] = $subtotal;
        }

        return $subtotals;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $entity instanceof DiscountAwareInterface;
    }

    /**
     * @return bool
     */
    protected function isFrontendUser()
    {
        return $this->tokenAccessor->getUser() instanceof CustomerUser;
    }

    /**
     * @param $discount OrderDiscount
     * @param $entity LineItemsAwareInterface
     *
     * @return float
     */
    protected function getSubtotalAmount($discount, $entity)
    {
        if ($discount->getType() === OrderDiscount::TYPE_PERCENT) {
            $lineItemSubtotal = $this->lineItemSubtotal->getSubtotal($entity);
            $subtotalAmount = $lineItemSubtotal->getAmount() / 100 * $discount->getPercent();
        } else {
            $subtotalAmount = $discount->getAmount();
        }

        return $subtotalAmount;
    }
}

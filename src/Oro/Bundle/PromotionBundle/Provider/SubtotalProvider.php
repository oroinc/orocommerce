<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for promotions.
 */
class SubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface, CacheAwareInterface
{
    use FeatureCheckerHolderTrait;

    public const TYPE = 'discount';
    public const ORDER_DISCOUNT_SUBTOTAL = 'order_discount_subtotal';
    public const SHIPPING_DISCOUNT_SUBTOTAL = 'shipping_discount_subtotal';
    public const ORDER_DISCOUNT_SUBTOTAL_SORT_ORDER = 100;
    public const SHIPPING_DISCOUNT_SUBTOTAL_SORT_ORDER = 300;

    private PromotionExecutor $promotionExecutor;
    private AppliedDiscountsProvider $appliedDiscountsProvider;
    private RoundingServiceInterface $rounding;
    private TranslatorInterface $translator;

    public function __construct(
        SubtotalProviderConstructorArguments $arguments,
        PromotionExecutor $promotionExecutor,
        AppliedDiscountsProvider $appliedDiscountsProvider,
        RoundingServiceInterface $roundingService,
        TranslatorInterface $translator
    ) {
        parent::__construct($arguments);
        $this->promotionExecutor = $promotionExecutor;
        $this->appliedDiscountsProvider = $appliedDiscountsProvider;
        $this->rounding = $roundingService;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubtotal($entity)
    {
        $discountContext = $this->promotionExecutor->execute($entity);

        return $this->createOrderAndShippingSubtotals(
            $entity,
            $discountContext->getTotalLineItemsDiscount() + $discountContext->getSubtotalDiscountTotal(),
            $discountContext->getShippingDiscountTotal()
        );
    }

    /**
     * @param Order $entity
     *
     * {@inheritDoc}
     */
    public function getCachedSubtotal($entity)
    {
        if (!$this->supportsCachedSubtotal($entity)) {
            throw new \RuntimeException(sprintf(
                'Can not get cached subtotals for entity "%s" because provider doesn\'t support it',
                get_class($entity)
            ));
        }

        $orderAmount = $this->appliedDiscountsProvider->getDiscountsAmountByOrder($entity);
        $shippingAmount = $this->appliedDiscountsProvider->getShippingDiscountsAmountByOrder($entity);

        return $this->createOrderAndShippingSubtotals($entity, $orderAmount, $shippingAmount);
    }

    /**
     * {@inheritDoc}
     */
    public function isSupported($entity)
    {
        return $this->isFeaturesEnabled() && $this->promotionExecutor->supports($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsCachedSubtotal($entity)
    {
        return $entity instanceof Order && $entity->getId();
    }

    private function createSubtotal(float $amount, string $currency, string $label, int $sortOrder): Subtotal
    {
        $subtotal = new Subtotal();
        $subtotal->setLabel($label);
        $subtotal->setType(self::TYPE);
        $subtotal->setVisible($amount > 0.0);
        $subtotal->setAmount($this->rounding->round($amount));
        $subtotal->setCurrency($currency);
        $subtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);
        $subtotal->setSortOrder($sortOrder);
        $subtotal->setRemovable(false);

        return $subtotal;
    }

    private function createOrderAndShippingSubtotals(object $entity, float $orderAmount, float $shippingAmount): array
    {
        $currency = $this->getBaseCurrency($entity);
        $orderDiscountSubtotal = $this->createSubtotal(
            $orderAmount,
            $currency,
            $this->translator->trans('oro.promotion.discount.subtotal.order.label'),
            self::ORDER_DISCOUNT_SUBTOTAL_SORT_ORDER
        );
        $shippingDiscountSubtotal = $this->createSubtotal(
            $shippingAmount,
            $currency,
            $this->translator->trans('oro.promotion.discount.subtotal.shipping.label'),
            self::SHIPPING_DISCOUNT_SUBTOTAL_SORT_ORDER
        );

        return [
            self::ORDER_DISCOUNT_SUBTOTAL => $orderDiscountSubtotal,
            self::SHIPPING_DISCOUNT_SUBTOTAL => $shippingDiscountSubtotal,
        ];
    }
}

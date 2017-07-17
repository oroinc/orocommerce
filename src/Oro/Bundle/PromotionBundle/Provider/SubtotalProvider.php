<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;

class SubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface, CacheAwareInterface
{
    const TYPE = 'discount';
    const NAME = 'oro_promotion.subtotal_discount';
    const ORDER_DISCOUNT_SUBTOTAL = 'order_discount_subtotal';
    const SHIPPING_DISCOUNT_SUBTOTAL = 'shipping_discount_subtotal';

    /**
     * @var PromotionExecutor
     */
    private $promotionExecutor;

    /**
     * @var AppliedDiscountsProvider
     */
    private $appliedDiscountsProvider;

    /**
     * @var RoundingServiceInterface
     */
    private $rounding;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var DiscountRecalculationProvider
     */
    private $discountRecalculationProvider;

    /**
     * @param UserCurrencyManager $currencyManager
     * @param PromotionExecutor $promotionExecutor
     * @param AppliedDiscountsProvider $appliedDiscountsProvider
     * @param RoundingServiceInterface $roundingService
     * @param TranslatorInterface $translator
     * @param DiscountRecalculationProvider $discountRecalculationProvider
     */
    public function __construct(
        UserCurrencyManager $currencyManager,
        PromotionExecutor $promotionExecutor,
        AppliedDiscountsProvider $appliedDiscountsProvider,
        RoundingServiceInterface $roundingService,
        TranslatorInterface $translator,
        DiscountRecalculationProvider $discountRecalculationProvider
    ) {
        parent::__construct($currencyManager);
        $this->promotionExecutor = $promotionExecutor;
        $this->appliedDiscountsProvider = $appliedDiscountsProvider;
        $this->rounding = $roundingService;
        $this->translator = $translator;
        $this->discountRecalculationProvider = $discountRecalculationProvider;
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
    public function getSubtotal($entity)
    {
        if (!$this->discountRecalculationProvider->isRecalculationRequired()) {
            return $this->getCachedSubtotal($entity);
        }

        $discountContext = $this->promotionExecutor->execute($entity);

        return $this->createOrderAndShippingSubtotals(
            $entity,
            $discountContext->getTotalLineItemsDiscount() + $discountContext->getSubtotalDiscountTotal(),
            $discountContext->getShippingDiscountTotal()
        );
    }

    /**
     * @param Order $entity
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $this->promotionExecutor->supports($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCachedSubtotal($entity)
    {
        return $entity instanceof Order && $entity->getId();
    }

    /**
     * @param float $amount
     * @param string $currency
     * @param string $label
     * @return Subtotal
     */
    private function createSubtotal($amount, $currency, $label): Subtotal
    {
        $subtotal = new Subtotal();
        $subtotal->setLabel($label);
        $subtotal->setType(self::TYPE);
        $subtotal->setVisible($amount > 0.0);
        $subtotal->setAmount($this->rounding->round($amount));
        $subtotal->setCurrency($currency);
        $subtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);

        return $subtotal;
    }

    /**
     * @param object $entity
     * @param float $orderAmount
     * @param float $shippingAmount
     * @return array
     */
    private function createOrderAndShippingSubtotals($entity, float $orderAmount, float $shippingAmount)
    {
        $currency = $this->getBaseCurrency($entity);
        $orderDiscountSubtotal = $this->createSubtotal(
            $orderAmount,
            $currency,
            $this->translator->trans('oro.promotion.discount.subtotal.order.label')
        );
        $shippingDiscountSubtotal = $this->createSubtotal(
            $shippingAmount,
            $currency,
            $this->translator->trans('oro.promotion.discount.subtotal.shipping.label')
        );

        return [
            self::ORDER_DISCOUNT_SUBTOTAL => $orderDiscountSubtotal,
            self::SHIPPING_DISCOUNT_SUBTOTAL => $shippingDiscountSubtotal,
        ];
    }
}

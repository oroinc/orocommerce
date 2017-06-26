<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Symfony\Component\Translation\TranslatorInterface;

class SubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'discount';
    const NAME = 'oro_promotion.subtotal_discount';

    /**
     * @var PromotionExecutor
     */
    private $promotionExecutor;

    /**
     * @var RoundingServiceInterface
     */
    private $rounding;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param UserCurrencyManager $currencyManager
     * @param PromotionExecutor $promotionExecutor
     * @param RoundingServiceInterface $roundingService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        UserCurrencyManager $currencyManager,
        PromotionExecutor $promotionExecutor,
        RoundingServiceInterface $roundingService,
        TranslatorInterface $translator
    ) {
        parent::__construct($currencyManager);
        $this->promotionExecutor = $promotionExecutor;
        $this->rounding = $roundingService;
        $this->translator = $translator;
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
        $discountContext = $this->promotionExecutor->execute($entity);
        $currency = $this->getBaseCurrency($entity);

        $orderSubtotal = $this->createSubtotal(
            $discountContext->getTotalLineItemsDiscount() + $discountContext->getSubtotalDiscountTotal(),
            $currency,
            $this->translator->trans('oro.promotion.discount.subtotal.order.label')
        );
        $shippingSubtotal = $this->createSubtotal(
            $discountContext->getShippingDiscountTotal(),
            $currency,
            $this->translator->trans('oro.promotion.discount.subtotal.shipping.label')
        );

        return [$orderSubtotal, $shippingSubtotal];
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $this->promotionExecutor->supports($entity);
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
}

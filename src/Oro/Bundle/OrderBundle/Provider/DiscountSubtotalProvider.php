<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Model\DiscountAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class DiscountSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'discount';
    const NAME = 'oro_order.subtotal_discount_cost';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var RoundingServiceInterface */
    protected $lineItemSubtotal;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     * @param LineItemSubtotalProvider $lineItemSubtotal
     * @param SecurityFacade $securityFacade
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        LineItemSubtotalProvider $lineItemSubtotal,
        SecurityFacade $securityFacade,
        UserCurrencyManager $currencyManager
    ) {
        parent::__construct($currencyManager);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->lineItemSubtotal = $lineItemSubtotal;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
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
        return $this->securityFacade->getLoggedUser() instanceof AccountUser;
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

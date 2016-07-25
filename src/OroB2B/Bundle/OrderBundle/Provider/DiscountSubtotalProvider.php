<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\OrderDiscount;
use OroB2B\Bundle\OrderBundle\Model\DiscountAwareInterface;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

class DiscountSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'discount';
    const NAME = 'orob2b_order.subtotal_discount_cost';

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
            $title = $this->translator->trans('orob2b.order.subtotals.' . self::TYPE);
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

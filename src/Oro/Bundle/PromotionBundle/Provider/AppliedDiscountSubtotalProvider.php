<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;

class AppliedDiscountSubtotalProvider implements SubtotalProviderInterface
{
    const NAME = 'oro_promotion.subtotal_discount';
    const TYPE = 'discount';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
        /** @var AppliedDiscount[] $appliedDiscounts */
        $appliedDiscounts = $this->doctrineHelper->getEntityRepositoryForClass(AppliedDiscount::class)->findBy([
            'order' => $entity
        ]);

        $groupedByCurrencies = [];
        foreach ($appliedDiscounts as $appliedDiscount) {
            if (!array_key_exists($appliedDiscount->getCurrency(), $groupedByCurrencies)) {
                $groupedByCurrencies[$appliedDiscount->getCurrency()] = $appliedDiscount->getAmount();
            }
            $groupedByCurrencies[$appliedDiscount->getCurrency()] += $appliedDiscount->getAmount();
        }

        $subtotals = [];
        foreach ($groupedByCurrencies as $currency => $amount) {
            $subtotal = new Subtotal();
            $subtotal->setOperation(Subtotal::OPERATION_IGNORE);
            $subtotal->setType(self::TYPE);
            $subtotal->setLabel('discount label');
            $subtotal->setVisible(true);

            $subtotal->setCurrency($currency);
            $subtotal->setAmount($amount);

            $subtotals[] = $subtotal;
        }

        return $subtotals;
    }

    /**
     * {@inheritDoc}
     */
    public function isSupported($entity)
    {
        if (!$entity instanceof Order) {
            return false;
        }
        if (!$this->doctrineHelper->getEntityRepositoryForClass(AppliedDiscount::class)->findBy(['order' => $entity])) {
            return false;
        }
        return true;
    }
}

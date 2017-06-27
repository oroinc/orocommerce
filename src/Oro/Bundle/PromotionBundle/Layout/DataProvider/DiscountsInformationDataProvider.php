<?php

namespace Oro\Bundle\PromotionBundle\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;

class DiscountsInformationDataProvider
{
    /**
     * @var PromotionExecutor
     */
    private $promotionExecutor;

    /**
     * @var UserCurrencyManager
     */
    private $currencyManager;

    /**
     * @param PromotionExecutor $promotionExecutor
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(PromotionExecutor $promotionExecutor, UserCurrencyManager $currencyManager)
    {
        $this->promotionExecutor = $promotionExecutor;
        $this->currencyManager = $currencyManager;
    }

    /**
     * @param object $sourceEntity
     * @return array|Price[]
     */
    public function getDiscountLineItemDiscounts($sourceEntity): array
    {
        $info = [];
        $currency = $this->currencyManager->getUserCurrency();
        if ($this->promotionExecutor->supports($sourceEntity)) {
            $discountContext = $this->promotionExecutor->execute($sourceEntity);
            foreach ($discountContext->getLineItems() as $lineItem) {
                $info[$lineItem->getSourceLineItem()->getId()] = Price::create(
                    $lineItem->getDiscountTotal(),
                    $currency
                );
            }
        }

        return $info;
    }
}

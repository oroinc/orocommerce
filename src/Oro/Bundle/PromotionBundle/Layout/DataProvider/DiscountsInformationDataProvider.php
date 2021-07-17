<?php

namespace Oro\Bundle\PromotionBundle\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DTO\ObjectStorage;

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

    public function __construct(PromotionExecutor $promotionExecutor, UserCurrencyManager $currencyManager)
    {
        $this->promotionExecutor = $promotionExecutor;
        $this->currencyManager = $currencyManager;
    }

    /**
     * @param object $sourceEntity
     * @return ObjectStorage
     */
    public function getDiscountLineItemDiscounts($sourceEntity): ObjectStorage
    {
        $info = new ObjectStorage();
        $currency = $this->currencyManager->getUserCurrency();
        if ($this->promotionExecutor->supports($sourceEntity)) {
            $discountContext = $this->promotionExecutor->execute($sourceEntity);
            foreach ($discountContext->getLineItems() as $lineItem) {
                $info->attach(
                    $lineItem->getSourceLineItem(),
                    [
                        'total' => Price::create($lineItem->getDiscountTotal(), $currency),
                        'details' => $lineItem->getDiscountsInformation()
                    ]
                );
            }
        }

        return $info;
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;

class UpdateLexemesPriceListListener
{
    /**
     * @var PriceRuleLexemeHandler
     */
    private $priceRuleLexemeHandler;

    /**
     * @param PriceRuleLexemeHandler $priceRuleLexemeHandler
     */
    public function __construct(PriceRuleLexemeHandler $priceRuleLexemeHandler)
    {
        $this->priceRuleLexemeHandler = $priceRuleLexemeHandler;
    }

    /**
     * @param PriceList $priceList
     */
    public function updateLexemes(PriceList $priceList)
    {
        $this->priceRuleLexemeHandler->updateLexemes($priceList);
    }
}

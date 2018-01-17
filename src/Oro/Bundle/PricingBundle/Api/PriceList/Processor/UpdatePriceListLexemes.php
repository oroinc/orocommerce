<?php

namespace Oro\Bundle\PricingBundle\Api\PriceList\Processor;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdatePriceListLexemes implements ProcessorInterface
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
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        $priceList = $context->getResult();
        if (!$priceList instanceof PriceList) {
            return;
        }

        $this->priceRuleLexemeHandler->updateLexemes($priceList);
    }
}

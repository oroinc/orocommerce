<?php

namespace Oro\Bundle\PricingBundle\Api\PriceRule\Processor;

use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * This processor updates lexemes on PriceRule change.
 */
class UpdateLexemesPriceRule implements ProcessorInterface
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
        $priceRule = $context->getResult();

        if (!$priceRule instanceof PriceRule) {
            return;
        }

        if (null === $priceRule->getPriceList()) {
            return;
        }

        $this->priceRuleLexemeHandler->updateLexemes($priceRule->getPriceList());
    }
}

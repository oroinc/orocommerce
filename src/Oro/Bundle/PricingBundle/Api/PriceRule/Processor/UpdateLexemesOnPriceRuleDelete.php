<?php

namespace Oro\Bundle\PricingBundle\Api\PriceRule\Processor;

use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * This processor uses default delete processor to delete a PriceRule, but saves it in a variable to have
 * access to it after context is removed in order to update lexemes by related price list.
 */
class UpdateLexemesOnPriceRuleDelete implements ProcessorInterface
{
    /**
     * @var PriceRuleLexemeHandler
     */
    private $priceRuleLexemeHandler;

    /**
     * @var ProcessorInterface
     */
    private $deleteProcessor;

    /**
     * @param PriceRuleLexemeHandler $priceRuleLexemeHandler
     * @param ProcessorInterface $deleteProcessor
     */
    public function __construct(PriceRuleLexemeHandler $priceRuleLexemeHandler, ProcessorInterface $deleteProcessor)
    {
        $this->priceRuleLexemeHandler = $priceRuleLexemeHandler;
        $this->deleteProcessor = $deleteProcessor;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        $priceRule = $context->getResult();

        $this->deleteProcessor->process($context);

        if (!$priceRule instanceof PriceRule) {
            return;
        }

        if (null === $priceRule->getPriceList()) {
            return;
        }

        $this->priceRuleLexemeHandler->updateLexemes($priceRule->getPriceList());
    }
}

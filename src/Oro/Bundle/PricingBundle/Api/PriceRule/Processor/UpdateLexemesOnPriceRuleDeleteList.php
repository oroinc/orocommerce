<?php

namespace Oro\Bundle\PricingBundle\Api\PriceRule\Processor;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * This processor uses default delete processor to delete PriceRules, but saves them in a variable to have
 * access to them after context is removed in order to update lexemes by related price lists.
 */
class UpdateLexemesOnPriceRuleDeleteList implements ProcessorInterface
{
    /**
     * @var PriceRuleLexemeHandler
     */
    private $priceRuleLexemeHandler;

    /**
     * @var ProcessorInterface
     */
    private $deleteListProcessor;

    /**
     * @param PriceRuleLexemeHandler $priceRuleLexemeHandler
     * @param ProcessorInterface $deleteListProcessor
     */
    public function __construct(PriceRuleLexemeHandler $priceRuleLexemeHandler, ProcessorInterface $deleteListProcessor)
    {
        $this->priceRuleLexemeHandler = $priceRuleLexemeHandler;
        $this->deleteListProcessor = $deleteListProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $priceRules = $context->getResult();

        $this->deleteListProcessor->process($context);

        if (false === is_array($priceRules)) {
            return;
        }

        $updatedPriceLists = $this->extractUpdatedPriceLists($priceRules);

        foreach ($updatedPriceLists as $updatedPriceList) {
            $this->priceRuleLexemeHandler->updateLexemes($updatedPriceList);
        }
    }

    /**
     * @param PriceRule[] $priceRules
     *
     * @return PriceList[]
     */
    private function extractUpdatedPriceLists(array $priceRules) : array
    {
        $updatedPriceLists = [];

        foreach ($priceRules as $priceRule) {
            if (!$priceRule instanceof PriceRule) {
                continue;
            }

            $priceList = $priceRule->getPriceList();

            if ($priceList === null) {
                continue;
            }

            if (array_key_exists($priceList->getId(), $updatedPriceLists)) {
                continue;
            }

            $updatedPriceLists[$priceList->getId()] = $priceList;
        }

        return $updatedPriceLists;
    }
}

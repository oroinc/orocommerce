<?php

namespace Oro\Bundle\PricingBundle\Expression\Preprocessor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;

class ProductAssignmentRuleExpressionPreprocessor implements ExpressionPreprocessorInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function process($expression)
    {
        $matches = [];
        preg_match_all('/pricelist\[(\d+)\]\.productAssignmentRule/', $expression, $matches);
        if (count($matches) === 2 && is_array($matches[1])) {
            $foundPriceListsIds = array_unique($matches[1]);
            foreach ($foundPriceListsIds as $priceListId) {
                $rule = '1 == 1';
                $priceList = $this->getPriceList($priceListId);

                if ($priceList && $priceList->getProductAssignmentRule()) {
                    $rule = $priceList->getProductAssignmentRule();
                }
                $expression = str_replace(
                    sprintf('pricelist[%d].productAssignmentRule', $priceListId),
                    $rule,
                    $expression
                );
            }
        }

        return $expression;
    }

    /**
     * @param int $priceListId
     * @return PriceList
     */
    protected function getPriceList($priceListId)
    {
        return $this->registry->getManagerForClass(PriceList::class)
            ->find(PriceList::class, $priceListId);
    }
}

<?php

namespace OroB2B\Bundle\PricingBundle\Handler;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;

class PriceRuleLexemeHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $priceRuleProvider;

    /**
     * @param ManagerRegistry $registry
     * @param ExpressionParser $parser
     * @param PriceRuleFieldsProvider $priceRuleProvider
     */
    public function __construct(
        ManagerRegistry $registry,
        ExpressionParser $parser,
        PriceRuleFieldsProvider $priceRuleProvider
    ) {
        $this->registry = $registry;
        $this->parser = $parser;
        $this->priceRuleProvider = $priceRuleProvider;
    }

    /**
     * @param PriceList $priceList
     */
    public function updateLexemes(PriceList $priceList)
    {
        $assignmentRule = $priceList->getProductAssignmentRule();

        $em = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceRuleLexeme');

        // Remove all lexemes for priceList if price list exist
        if ($priceList->getId()) {
            $em->getRepository('OroB2BPricingBundle:PriceRuleLexeme')->deleteByPriceList($priceList);
        }

        //Anew add lexemes for priceList
        $priceRules = $priceList->getPriceRules();

        $lexemes = [];

        if ($assignmentRule) {
            $assignmentRuleLexemes = $this->parser->getUsedLexemes($assignmentRule);
            $lexemes = $this->prepareLexemes($assignmentRuleLexemes, $priceList, null);
        }

        foreach ($priceRules as $rule) {
            $conditionRules = $this->parser->getUsedLexemes($rule->getRuleCondition());
            $priceRules = $this->parser->getUsedLexemes($rule->getRule());
            $uniqueLexemes = $this->mergeLexemes($conditionRules, $priceRules);
            $lexemes = array_merge($this->prepareLexemes($uniqueLexemes, $priceList, $rule), $lexemes);
        }

        foreach ($lexemes as $lexeme) {
            $em->persist($lexeme);
        }

        $em->flush();
    }
    
    /**
     * @param array $lexemes
     *  [
     *     <className> => [<fieldName1>,<fieldName2>..],
     * ]
     * @param PriceList $priceList
     * @param PriceRule|null $priceRule
     * @return PriceRuleLexeme[]
     */
    protected function prepareLexemes($lexemes, PriceList $priceList, PriceRule $priceRule = null)
    {
        $lexemeEntities = [];
        foreach ($lexemes as $class => $fieldNames) {
            $realClassName = $this->priceRuleProvider->getRealClassName($class);
            foreach ($fieldNames as $fieldName) {
                $lexeme = new PriceRuleLexeme();
                $lexeme->setPriceRule($priceRule);
                $lexeme->setClassName($realClassName);
                $lexeme->setFieldName($fieldName);
                $lexeme->setPriceList($priceList);

                if ($realClassName ===  PriceAttributeProductPrice::class) {
                    $classPath = explode("::", $class);
                    $fieldName = end($classPath);

                    /** @var PriceAttributePriceList $relation */
                    $relation = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceAttributePriceList')
                        ->getRepository('OroB2BPricingBundle:PriceAttributePriceList')
                        ->findOneBy(['fieldName' => $fieldName]);

                    $lexeme->setRelationId($relation->getId());
                } elseif ($realClassName ===  PriceList::class) {
                    //@TODO set relation for base price list BB-3372
                }

                $lexemeEntities[] = $lexeme;
            }
        }

        return $lexemeEntities;
    }

    /**
     * @param array $lexemes1
     * @param array $lexemes2
     * @return array
     */
    protected function mergeLexemes(array $lexemes1, array $lexemes2)
    {
        $classes = array_unique(array_merge(array_keys($lexemes1), array_keys($lexemes2)));
        $result = [];
        foreach ($classes as $class) {
            $fields = [];
            if (array_key_exists($class, $lexemes1)) {
                $fields = $lexemes1[$class];
            }
            if (array_key_exists($class, $lexemes2)) {
                $fields = array_merge($lexemes2[$class], $fields);
            }
            $result[$class] = array_unique($fields);
        }

        return $result;
    }
}

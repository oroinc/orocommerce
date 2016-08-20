<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;

class PriceRuleLexemeHandler
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $priceRuleProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ExpressionParser $parser
     * @param PriceRuleFieldsProvider $priceRuleProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExpressionParser $parser,
        PriceRuleFieldsProvider $priceRuleProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->parser = $parser;
        $this->priceRuleProvider = $priceRuleProvider;
    }

    /**
     * @param PriceList $priceList
     */
    public function updateLexemes(PriceList $priceList)
    {
        $assignmentRule = $priceList->getProductAssignmentRule();

        $em = $this->doctrineHelper->getEntityManager(PriceRuleLexeme::class);

        // Remove all lexemes for priceList if price list exist
        if ($priceList->getId()) {
            $em->getRepository(PriceRuleLexeme::class)->deleteByPriceList($priceList);
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
            if (strpos($class, '::') !== false) {
                list($containerClass, $fieldName) = explode('::', $class);
                $lexeme = new PriceRuleLexeme();
                $lexeme->setPriceRule($priceRule);
                $lexeme->setPriceList($priceList);
                $lexeme->setClassName($containerClass);
                $lexeme->setFieldName($fieldName);
                $lexemeEntities[] = $lexeme;
            }

            foreach ($fieldNames as $fieldName) {
                $lexeme = new PriceRuleLexeme();
                $lexeme->setPriceRule($priceRule);
                $lexeme->setClassName($realClassName);
                $lexeme->setFieldName(
                    $fieldName ? $fieldName : $this->doctrineHelper->getSingleEntityIdentifierFieldName($realClassName)
                );
                $lexeme->setPriceList($priceList);

                if ($realClassName ===  PriceAttributeProductPrice::class) {
                    $relation = $this->getPriceAttributeRelationByClass($class);
                    $lexeme->setRelationId($relation->getId());
                } elseif ($realClassName ===  ProductPrice::class) {
                    //@TODO set relation id for base price list BB-3273
                }

                $lexemeEntities[] = $lexeme;
            }
        }

        return $lexemeEntities;
    }

    /**
     * @param $class
     * @return PriceAttributePriceList PriceAttributePriceList
     */
    protected function getPriceAttributeRelationByClass($class)
    {
        $classPath = explode('::', $class);
        $fieldName = end($classPath);

        return $this->doctrineHelper->getEntityRepository(PriceAttributePriceList::class)
            ->findOneBy(['fieldName' => $fieldName]);
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

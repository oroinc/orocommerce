<?php

namespace OroB2B\Bundle\PricingBundle\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;

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
     * @param ManagerRegistry $registry
     * @param ExpressionParser $parser
     */
    public function __construct(ManagerRegistry $registry, ExpressionParser $parser)
    {
        $this->registry = $registry;
        $this->parser = $parser;
    }

    /**
     * @param PriceList $priceList
     */
    public function updateLexemes(PriceList $priceList)
    {
        $priceRules = $priceList->getPriceRules();
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceRuleLexeme');
        $existLexemes = $em->getRepository('OroB2BPricingBundle:PriceRuleLexeme')->getLexemesByRules($priceRules);
        $assignmentRule = $priceList->getProductAssignmentRule();
        $newLexemes = [];
        $lexemesToRemove = [];
        if ($assignmentRule) {
            $assignmentRules = $this->parser->getUsedLexemes($assignmentRule);
            $newLexemes = $this->getNewLexemes($existLexemes, $assignmentRules, null, $priceList);
            $lexemesToRemove = $this->getLexemesToRemove($existLexemes, $assignmentRules, null, $priceList);
        }

        foreach ($priceRules as $rule) {
            $conditionRules = $this->parser->getUsedLexemes($rule->getRuleCondition());
            $priceRules = $this->parser->getUsedLexemes($rule->getRule());
            $uniqueLexemes = $this->mergeLexemes($conditionRules, $priceRules);
            $newLexemes = array_merge($this->getNewLexemes($existLexemes, $uniqueLexemes, $rule), $newLexemes);
            $lexemesToRemove = array_merge(
                $this->getLexemesToRemove($existLexemes, $uniqueLexemes, $rule),
                $lexemesToRemove
            );
        }
        foreach ($newLexemes as $newLexeme) {
            $em->persist($newLexeme);
        }
        foreach ($lexemesToRemove as $lexemeToRemove) {
            $em->remove($lexemeToRemove);
        }
        $em->flush();
    }

    /**
     * @param PriceRuleLexeme[] $existLexemes
     * @param array $uniqueUsedLexemes
     *  [
     *     <className> => [<fieldName1>,<fieldName2>..],
     * ]
     * @param PriceRule|null $priceRule
     * @param PriceList|null $priceList
     * @return PriceRuleLexeme[]
     */
    protected function getNewLexemes(
        $existLexemes,
        $uniqueUsedLexemes,
        PriceRule $priceRule = null,
        PriceList $priceList = null
    ) {
        $newLexemes = [];
        foreach ($uniqueUsedLexemes as $class => $fieldNames) {
            foreach ($fieldNames as $fieldName) {
                if (!$this->checkLexemeExistInDatabase($existLexemes, $class, $fieldName, $priceRule, $priceList)) {
                    $lexeme = new PriceRuleLexeme();
                    $lexeme->setPriceRule($priceRule);
                    $lexeme->setClassName($class);
                    $lexeme->setFieldName($fieldName);
                    $lexeme->setPriceList($priceList);
                    $newLexemes[] = $lexeme;
                }
            }
        }

        return $newLexemes;
    }

    /**
     * @param PriceRuleLexeme[] $existLexemes
     * @param array $uniqueUsedLexemes
     * @param PriceRule $priceRule
     * @param PriceList|null $priceList
     * @return PriceRuleLexeme[]
     */
    protected function getLexemesToRemove(
        $existLexemes,
        $uniqueUsedLexemes,
        PriceRule $priceRule = null,
        PriceList $priceList = null
    ) {
        $lexemesToRemove = [];
        foreach ($existLexemes as $existLexeme) {
            if ($this->checkPriceRuleInLexeme($existLexeme, $priceRule)
                && $this->checkPriceListInLexeme($existLexeme, $priceList)
                && !$this->checkExistInUsed(
                    $existLexeme,
                    $uniqueUsedLexemes,
                    $priceRule,
                    $priceList
                )
            ) {
                $lexemesToRemove[] = $existLexeme;
            }
        }

        return $lexemesToRemove;
    }

    /**
     * @param PriceRuleLexeme[] $existLexemes
     * @param string $class
     * @param string $fieldName
     * @param PriceRule $priceRule
     * @param PriceList|null $priceList
     * @return bool
     */
    protected function checkLexemeExistInDatabase(
        $existLexemes,
        $class,
        $fieldName,
        PriceRule $priceRule = null,
        PriceList $priceList = null
    ) {
        foreach ($existLexemes as $existLexeme) {
            if ($this->checkEquals($existLexeme, $class, $fieldName, $priceRule, $priceList)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PriceRuleLexeme $existLexeme
     * @param array $uniqueUsedLexemes
     * @param PriceRule|null $priceRule
     * @param PriceList|null $priceList
     * @return bool
     */
    protected function checkExistInUsed(
        $existLexeme,
        $uniqueUsedLexemes,
        PriceRule $priceRule = null,
        PriceList $priceList = null
    ) {

        foreach ($uniqueUsedLexemes as $class => $fieldNames) {
            foreach ($fieldNames as $fieldName) {
                if ($this->checkEquals($existLexeme, $class, $fieldName, $priceRule, $priceList)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param PriceRuleLexeme $lexeme
     * @param string $class
     * @param string $field
     * @param PriceRule|null $priceRule
     * @param PriceList|null $priceList
     * @return bool
     */
    protected function checkEquals(
        PriceRuleLexeme $lexeme,
        $class,
        $field,
        PriceRule $priceRule = null,
        PriceList $priceList = null
    ) {
        if ($lexeme->getClassName() === $class
            && $lexeme->getFieldName() === $field
            && $this->checkPriceRuleInLexeme($lexeme, $priceRule)
            && $this->checkPriceListInLexeme($lexeme, $priceList)
        ) {
            return true;
        }

        return false;
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

    /**
     * @param PriceRuleLexeme $lexeme
     * @param PriceList|null $priceList
     * @return bool
     */
    protected function checkPriceListInLexeme(PriceRuleLexeme $lexeme, PriceList $priceList = null)
    {
        $priceListFromLexeme = $lexeme->getPriceList();
        if ($priceListFromLexeme && $priceList) {
            $priceListEquals = $priceListFromLexeme->getId() === $priceList->getId();

            return $priceListEquals;
        } else {
            $priceListEquals = $priceListFromLexeme === $priceList;

            return $priceListEquals;
        }
    }

    /**
     * @param PriceRuleLexeme $lexeme
     * @param PriceRule|null $priceRule
     * @return bool
     */
    protected function checkPriceRuleInLexeme(PriceRuleLexeme $lexeme, PriceRule $priceRule = null)
    {
        $priceRuleFromLexeme = $lexeme->getPriceRule();
        if ($priceRuleFromLexeme && $priceRule) {
            $priceRuleEquals = $priceRuleFromLexeme->getId() === $priceRule->getId();

            return $priceRuleEquals;
        } else {
            $priceListEquals = $priceRuleFromLexeme === $priceRule;

            return $priceListEquals;
        }
    }
}

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
        $assignmentRules = $this->parser->getUsedLexemes($priceList->getProductAssignmentRule());
        $priceRules = $priceList->getPriceRules();
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceRuleLexeme');
        $existLexemes = $em->getRepository('OroB2BPricingBundle:PriceRuleLexeme')->getLexemesByRules($priceRules);
        $newLexemes = [];
        $lexemesToRemove = [];
        foreach ($priceRules as $rule) {
            $conditionRules = $this->parser->getUsedLexemes($rule->getRuleCondition());
            $priceRules = $this->parser->getUsedLexemes($rule->getRule());
            $uniqueLexemes = $this->mergeLexemes($conditionRules, $priceRules);
            $uniqueUsedLexemes = $this->mergeLexemes($uniqueLexemes, $assignmentRules);
            $newLexemes = array_merge($this->getNewLexemes($existLexemes, $uniqueUsedLexemes, $rule), $newLexemes);
            $lexemesToRemove = array_merge(
                $this->getLexemesToRemove($existLexemes, $uniqueUsedLexemes, $rule),
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
     * @param PriceRule $priceRule
     * @return PriceRuleLexeme[]
     */
    protected function getNewLexemes($existLexemes, $uniqueUsedLexemes, PriceRule $priceRule)
    {
        $newLexemes = [];
        foreach ($uniqueUsedLexemes as $class => $fieldNames) {
            foreach ($fieldNames as $fieldName) {
                if (!$this->checkLexemeExistInDatabase($existLexemes, $class, $fieldName, $priceRule)) {
                    $lexeme = new PriceRuleLexeme();
                    $lexeme->setPriceRule($priceRule);
                    $lexeme->setClassName($class);
                    $lexeme->setFieldName($fieldName);
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
     * @return PriceRuleLexeme[]
     */
    protected function getLexemesToRemove($existLexemes, $uniqueUsedLexemes, PriceRule $priceRule)
    {
        $lexemesToRemove = [];
        foreach ($existLexemes as $existLexeme) {
            if ($existLexeme->getPriceRule()->getId() === $priceRule->getId() &&
                !$this->checkExistInUsed($existLexeme, $uniqueUsedLexemes, $priceRule)
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
     * @return bool
     */
    protected function checkLexemeExistInDatabase($existLexemes, $class, $fieldName, PriceRule $priceRule)
    {
        foreach ($existLexemes as $existLexeme) {
            if ($this->checkEquals($existLexeme, $class, $fieldName, $priceRule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PriceRuleLexeme $existLexeme
     * @param array $uniqueUsedLexemes
     * @param PriceRule $priceRule
     * @return bool
     */
    protected function checkExistInUsed($existLexeme, $uniqueUsedLexemes, PriceRule $priceRule)
    {
        foreach ($uniqueUsedLexemes as $class => $fieldNames) {
            foreach ($fieldNames as $fieldName) {
                if ($this->checkEquals($existLexeme, $class, $fieldName, $priceRule)) {
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
     * @param PriceRule $priceRule
     * @return bool
     */
    protected function checkEquals(PriceRuleLexeme $lexeme, $class, $field, PriceRule $priceRule)
    {
        if ($lexeme->getClassName() === $class
            && $lexeme->getFieldName() === $field
            && $lexeme->getPriceRule()->getId() === $priceRule->getId()
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
}

<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;

/**
 * Prepare and save price rule lexemes based on price list expressions stored in price rules and product assignment rule
 */
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
     * @var FieldsProviderInterface
     */
    protected $priceRuleProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExpressionParser $parser,
        FieldsProviderInterface $priceRuleProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->parser = $parser;
        $this->priceRuleProvider = $priceRuleProvider;
    }

    public function updateLexemesWithoutFlush(PriceList $priceList)
    {
        $assignmentRule = $priceList->getProductAssignmentRule();

        $em = $this->doctrineHelper->getEntityManager(PriceRuleLexeme::class);

        // Remove all lexemes for priceList if price list exist
        if ($priceList->getId()) {
            /** @var PriceRuleLexemeRepository $repository */
            $repository = $em->getRepository(PriceRuleLexeme::class);
            $repository->deleteByPriceList($priceList);
        }

        //Anew add lexemes for priceList
        $priceRules = $priceList->getPriceRules();

        $lexemes = [];
        if ($assignmentRule) {
            $assignmentRuleLexemes = $this->parser->getUsedLexemes($assignmentRule, true);
            $lexemes[] = $this->prepareLexemes($assignmentRuleLexemes, $priceList, null);
        }

        foreach ($priceRules as $rule) {
            $conditionRules = $this->parser->getUsedLexemes($rule->getRuleCondition(), true);
            $priceRules = $this->parser->getUsedLexemes($rule->getRule(), true);
            $uniqueLexemes = $this->mergeLexemes($conditionRules, $priceRules);

            $uniqueLexemes = $this->mergeLexemes(
                $uniqueLexemes,
                $this->parser->getUsedLexemes($rule->getQuantityExpression(), true)
            );
            $uniqueLexemes = $this->mergeLexemes(
                $uniqueLexemes,
                $this->parser->getUsedLexemes($rule->getProductUnitExpression(), true)
            );
            $uniqueLexemes = $this->mergeLexemes(
                $uniqueLexemes,
                $this->parser->getUsedLexemes($rule->getCurrencyExpression(), true)
            );

            $lexemes[] = $this->prepareLexemes($uniqueLexemes, $priceList, $rule);
        }

        if ($lexemes) {
            $lexemes = array_merge(...$lexemes);
        }
        foreach ($lexemes as $lexeme) {
            $em->persist($lexeme);
        }
    }

    public function updateLexemes(PriceList $priceList)
    {
        $this->updateLexemesWithoutFlush($priceList);
        $em = $this->doctrineHelper->getEntityManager(PriceRuleLexeme::class);
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
    protected function prepareLexemes(array $lexemes, PriceList $priceList, ?PriceRule $priceRule = null)
    {
        $lexemeEntities = [];
        /**
         * @var string $class
         * @var array $fieldNames
         */
        foreach ($lexemes as $class => $fieldNames) {
            $containerId = null;
            if (str_contains($class, '|')) {
                [$class, $containerId] = explode('|', $class);
            }

            if (str_contains($class, '::')) {
                [$containerClass, $fieldName] = explode('::', $class);
                $lexeme = new PriceRuleLexeme();
                $lexeme->setPriceRule($priceRule);
                $lexeme->setPriceList($priceList);
                $lexeme->setClassName($containerClass);
                $lexeme->setFieldName($fieldName);
                $lexeme->setRelationId($containerId);
                $lexemeEntities[] = $lexeme;
            }

            $realClassName = $this->priceRuleProvider->getRealClassName($class);
            if ($realClassName === PriceAttributeProductPrice::class) {
                $containerId = $this->getPriceAttributeRelationByClass($class, $priceList->getOrganization())->getId();
            }
            foreach ($fieldNames as $fieldName) {
                $lexeme = new PriceRuleLexeme();
                $lexeme->setPriceRule($priceRule);
                $lexeme->setClassName($realClassName);
                $lexeme->setRelationId($containerId);
                $lexeme->setFieldName(
                    $fieldName ?: $this->doctrineHelper->getSingleEntityIdentifierFieldName($realClassName)
                );
                $lexeme->setPriceList($priceList);

                $lexemeEntities[] = $lexeme;
            }
        }

        return $lexemeEntities;
    }

    protected function getPriceAttributeRelationByClass(
        string $class,
        Organization $organization
    ): PriceAttributePriceList {
        $classPath = explode('::', $class);
        $fieldName = end($classPath);

        return $this->doctrineHelper->getEntityRepository(PriceAttributePriceList::class)
            ->findOneBy(['fieldName' => $fieldName, 'organization' => $organization]);
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

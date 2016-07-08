<?php

namespace OroB2B\Bundle\PricingBundle\Compiler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceListRuleCompiler
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    /**
     * @var array
     */
    protected $fieldsOrder = [
        'product',
        'priceList',
        'unit',
        'currency',
        'quantity',
        'productSku',
        'value',
        'priceRule'
    ];

    /**
     * @param Registry $registry
     * @param ExpressionParser $parser
     */
    public function __construct(Registry $registry, ExpressionParser $parser)
    {
        $this->registry = $registry;
        $this->expressionParser = $parser;
    }

    /**
     * @param PriceRule $rule
     * @param Product $product
     * @return QueryBuilder
     */
    public function compileRule(PriceRule $rule, Product $product = null)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToProduct');
        $qb = $em->createQueryBuilder();
        $qb->from('OroB2BPricingBundle:PriceListToProduct', 'priceListToProduct')
            ->join('priceListToProduct.product', 'product')
            ->where($qb->expr()->eq('priceListToProduct.priceList', ':priceList'))
            ->setParameter('priceList', $rule->getPriceList());

        $this->modifySelectPart($qb, $rule);

        if ($product) {
            $qb->andWhere($qb->expr()->eq('priceListToProduct.product', ':product'))
                ->setParameter('product', $product);
        }

        $qb->andWhere($rule->getRuleCondition());

        $this->restrictByExistPrices($qb, $rule);

        return $qb;
    }

    /**
     * @return array
     */
    public function getFieldsOrder()
    {
        return $this->fieldsOrder;
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     */
    protected function modifySelectPart(QueryBuilder $qb, PriceRule $rule)
    {
        $fieldsMap = [
            'product' => 'product.id',
            'productSku' => 'product.sku',
            'priceList' => (string)$qb->expr()->literal($rule->getPriceList()->getId()),
            'unit' => (string)$qb->expr()->literal($rule->getProductUnit()->getCode()),
            'currency' => (string)$qb->expr()->literal($rule->getCurrency()),
            'quantity' => (string)$qb->expr()->literal($rule->getQuantity()),
            'value' => $rule->getRule(),
            'priceRule' => (string)$qb->expr()->literal($rule->getId())
        ];
        $select = [];
        $qb->select();
        foreach ($this->getFieldsOrder() as $fieldName) {
            $select[] = $fieldsMap[$fieldName];
        }
        $qb->select($select);
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     */
    protected function restrictByExistPrices(QueryBuilder $qb, PriceRule $rule)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass('OroB2BPricingBundle:ProductPrice');
        $subQb = $em->createQueryBuilder()
            ->from('OroB2BPricingBundle:ProductPrice', 'productPriceOld');
        $subQb->where(
            $subQb->expr()->andX(
                $subQb->expr()->eq('productPriceOld.priceList', ':priceListOld'),
                $subQb->expr()->eq('productPriceOld.product', 'product'),
                $subQb->expr()->eq('productPriceOld.unit', ':unitOld'),
                $subQb->expr()->eq('productPriceOld.currency', ':currencyOld'),
                $subQb->expr()->eq('productPriceOld.quantity', ':quantityOld'),
                $subQb->expr()->isNull('productPriceOld.priceRule')
            )
        );

        $qb->setParameter('priceListOld', $rule->getPriceList()->getId())
            ->setParameter('unitOld', $rule->getProductUnit()->getCode())
            ->setParameter('currencyOld', $rule->getCurrency())
            ->setParameter('quantityOld', $rule->getQuantity());

        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists(
                    $subQb->getQuery()->getDQL()
                )
            )
        );
    }

    /**
     * @param PriceRule $rule
     * @param QueryBuilder $qb
     */
    protected function addPriceSelect(PriceRule $rule, QueryBuilder $qb)
    {
        $ruleTree = $this->expressionParser->parse($rule->getRule());

        $this->addMissedJoins($rule, $qb);
    }

    /**
     * @param PriceRule $rule
     * @param QueryBuilder $qb
     * @return array
     */
    protected function addMissedJoins(PriceRule $rule, QueryBuilder $qb)
    {
        $knownMappings = [
            'OroB2B\Bundle\ProductBundle\Entity\Product' => 'product'
        ];
        $usedLexemes = $this->expressionParser->getUsedLexemes($rule->getRule());
        foreach ($usedLexemes as $className => $fields) {
            $relation = null;
            if (strpos($className, '::') !== false) {
                list($className, $relation) = explode('::', $className);
            }

            if ($className === 'OroB2B\Bundle\CatalogBundle\Entity\Category') {
                $knownMappings[$className] = 'category';

                $qb->leftJoin(
                    'OroB2BCatalogBundle:Category',
                    'category',
                    Join::WITH,
                    'product MEMBER OF category.products'
                );
            } elseif ($className === 'OroB2B\Bundle\PricingBundle\Entity\PriceList') {
                // TODO: Fix join to price list. Relation from rule to base price list must be used instead
                $priceListId = $rule->getPriceList()->getId();
                $alias = 'priceList' . $priceListId;
                $knownMappings[$className . '_' . $priceListId] = $alias;

                // TODO: Clarify which unit, quantity and currency restriction should be applied
                $qb->leftJoin(
                    'OroB2B\Bundle\PricingBundle\Entity\ProductPrice',
                    $alias,
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq($alias . '.product', 'product'),
                        $qb->expr()->eq($alias . '.priceList', $priceListId)
                    )
                );
            } else {
                throw new \InvalidArgumentException(sprintf('Class "%s" is not supported', $className));
            }

            if ($relation) {
                $classMetadata = $this->registry->getManagerForClass($className)
                    ->getClassMetadata($className);

//                $classMetadata->get
            }
        }

        return $knownMappings;
    }
}

<?php

namespace OroB2B\Bundle\PricingBundle\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;
use OroB2B\Bundle\PricingBundle\Expression\NodeToQueryDesignerConverter;
use OroB2B\Bundle\PricingBundle\Query\PriceListExpressionQueryConverter;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceListRuleCompiler
{
    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    /**
     * @var NodeToQueryDesignerConverter
     */
    protected $nodeConverter;

    /**
     * @var PriceListExpressionQueryConverter
     */
    protected $queryConverter;

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
        'priceRule',
    ];

    /**
     * @param ExpressionParser $parser
     * @param NodeToQueryDesignerConverter $nodeConverter
     * @param PriceListExpressionQueryConverter $queryConverter
     */
    public function __construct(
        ExpressionParser $parser,
        NodeToQueryDesignerConverter $nodeConverter,
        PriceListExpressionQueryConverter $queryConverter
    ) {
        $this->expressionParser = $parser;
        $this->nodeConverter = $nodeConverter;
        $this->queryConverter = $queryConverter;
    }

    /**
     * @param PriceRule $rule
     * @param Product $product
     * @return QueryBuilder
     */
    public function compileRule(PriceRule $rule, Product $product = null)
    {
        $qb = $this->createQueryBuilder($rule);
        $rootAlias = reset($qb->getRootAliases());

        $qb
            ->join(
                'OroB2BPricingBundle:PriceListToProduct',
                'priceListToProduct',
                Join::WITH,
                $qb->expr()->eq('priceListToProduct.product', $rootAlias)
            )
            ->andWhere($qb->expr()->eq('priceListToProduct.priceList', ':priceList'))
            ->setParameter('priceList', $rule->getPriceList());

        $this->modifySelectPart($qb, $rule, $rootAlias);

        if ($product) {
            $qb->andWhere($qb->expr()->eq($rootAlias, ':product'))
                ->setParameter('product', $product);
        }
        $this->restrictByExistPrices($qb, $rule, $rootAlias);

        return $qb;
    }

    /**
     * @param PriceRule $rule
     * @return QueryBuilder
     */
    protected function createQueryBuilder(PriceRule $rule)
    {
        $expression = sprintf('%s and (%s) > 0', $rule->getRuleCondition(), $rule->getRule());
        $node = $this->expressionParser->parse($expression);
        $source = $this->nodeConverter->convert($node);
        return $this->queryConverter->convert($source);
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
     * @param string $rootAlias
     */
    protected function modifySelectPart(QueryBuilder $qb, PriceRule $rule, $rootAlias)
    {
        $fieldsMap = [
            'product' => $rootAlias . '.id',
            'productSku' => $rootAlias . '.sku',
            'priceList' => (string)$qb->expr()->literal($rule->getPriceList()->getId()),
            'unit' => (string)$qb->expr()->literal($rule->getProductUnit()->getCode()),
            'currency' => (string)$qb->expr()->literal($rule->getCurrency()),
            'quantity' => (string)$qb->expr()->literal($rule->getQuantity()),
            'priceRule' => (string)$qb->expr()->literal($rule->getId()),
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
     * @param string $rootAlias
     */
    protected function restrictByExistPrices(QueryBuilder $qb, PriceRule $rule, $rootAlias)
    {
        /** @var EntityManagerInterface $em */
        $em = $qb->getEntityManager();
        $subQb = $em->createQueryBuilder()
            ->from('OroB2BPricingBundle:ProductPrice', 'productPriceOld')
            ->select('productPriceOld');
        $subQb->where(
            $subQb->expr()->andX(
                $subQb->expr()->eq('productPriceOld.product', $rootAlias),
                $subQb->expr()->eq('productPriceOld.priceList', ':priceListOld'),
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
}

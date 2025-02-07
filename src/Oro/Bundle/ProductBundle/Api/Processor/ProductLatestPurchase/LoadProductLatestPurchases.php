<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\ProductLatestPurchase;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Util\ComparisonExpressionsVisitor;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Api\Model\ProductLatestPurchase;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Loads product latest purchases.
 */
class LoadProductLatestPurchases implements ProcessorInterface
{
    private const array FIELD_MAPPING = [
        'productId' => 'oi.product',
        'customerId' => 'o.customer',
        'hierarchicalCustomer' => 'o.customer',
        'customerUserId' => 'o.customerUser',
        'websiteId' => 'o.website',
        'unit' => 'oi.productUnitCode',
        'currency' => 'oi.currency'
    ];

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private OwnerTreeProviderInterface $customerTreeProvider
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */
        $criteria = $context->getCriteria();

        $latestIdentifiers = $this->getLatestIdentifiers($criteria);
        if (empty($latestIdentifiers)) {
            $context->setResult([]);
            return;
        }

        $results = $this->getPurchaseDetails($criteria, $latestIdentifiers);

        $data = $this->mapToApiModel($criteria, $results);
        usort(
            $data,
            static fn (ProductLatestPurchase $a, ProductLatestPurchase $b) => strcmp($a->getId(), $b->getId())
        );

        $context->setResult($data);
    }

    private function extractFilterValues(Criteria $criteria, string $field): ?array
    {
        $visitor = new ComparisonExpressionsVisitor();
        $visitor->dispatch($criteria->getWhereExpression());
        $comparisons = $visitor->getComparisons();

        foreach ($comparisons as $comparison) {
            if ($comparison->getField() === $field) {
                if (!in_array($comparison->getOperator(), [Comparison::EQ, Comparison::IN], true)) {
                    throw new \InvalidArgumentException(sprintf(
                        'The "%s" operator is not supported for the "%s" filter.',
                        $comparison->getOperator(),
                        $field
                    ));
                }

                $values = $comparison->getValue()->getValue();
                return is_array($values) ? $values : [$values];
            }
        }

        return null;
    }

    /**
     * @param int[] $customerIds
     * @return array<int,int[]>
     */
    private function getCustomerTree(array $customerIds): array
    {
        $hierarchy = [];

        foreach ($customerIds as $customerId) {
            $hierarchy[$customerId] = $this->customerTreeProvider
                ->getTree()
                ->getSubordinateBusinessUnitIds($customerId);
        }

        return $hierarchy;
    }

    private function getGroupByFields(Criteria $criteria): array
    {
        $groupByFields = ['oi.product'];
        foreach (['customerId', 'customerUserId', 'websiteId', 'unit', 'currency'] as $filterField) {
            if ($this->extractFilterValues($criteria, $filterField) && isset(self::FIELD_MAPPING[$filterField])) {
                $groupByFields[] = self::FIELD_MAPPING[$filterField];
            }
        }

        return $groupByFields;
    }

    private function getLatestIdentifiers(Criteria $criteria): array
    {
        $em = $this->doctrineHelper->getEntityManager(OrderLineItem::class);
        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $groupByFields = $this->getGroupByFields($criteria);

        $formatFields = array_map(function (string $field) {
            return !in_array($field, ['oi.productUnitCode', 'oi.currency']) ? "IDENTITY(%s)" : '%s';
        }, $groupByFields);

        $qb->select(
            "CONCAT_WS('_',
                " . QueryBuilderUtil::sprintf(implode(',', $formatFields), ...$groupByFields) . ",
                MAX(o.createdAt)
            ) as latestIdentifier"
        )
            ->from(OrderLineItem::class, 'oi')
            ->innerJoin('oi.orders', 'o');

        $this->applyCriteriaToQueryBuilder($criteria, $qb);

        foreach ($groupByFields as $field) {
            QueryBuilderUtil::checkField($field);
            $qb->addGroupBy($field);
        }

        return $qb->getQuery()->getArrayResult();
    }

    private function getPurchaseDetails(Criteria $criteria, array $latestIdentifiers): array
    {
        $em = $this->doctrineHelper->getEntityManager(OrderLineItem::class);
        $qb = $em->createQueryBuilder();

        $groupByFields = $this->getGroupByFields($criteria);
        $formatFields = array_map(function (string $field) {
            return !in_array($field, ['oi.productUnitCode', 'oi.currency']) ? "IDENTITY(%s)" : '%s';
        }, $groupByFields);

        $groupByFields[] = 'o.createdAt';
        $formatFields[] = '%s';

        $qb->select(
            'IDENTITY(o.website) as websiteId',
            'IDENTITY(o.customer) as customerId',
            'IDENTITY(o.customerUser) as customerUserId',
            'IDENTITY(oi.product) as productId',
            'oi.productUnitCode as unit',
            'oi.currency as currency',
            'MIN(oi.value) as price',
            'o.createdAt as purchasedAt'
        )
            ->from(OrderLineItem::class, 'oi')
            ->innerJoin('oi.orders', 'o')
            ->where(
                $qb->expr()->in(
                    "CONCAT_WS('_', " .
                    QueryBuilderUtil::sprintf(implode(',', $formatFields), ...$groupByFields) .
                    ")",
                    ":latestIdentifiers"
                )
            )
            ->setParameter("latestIdentifiers", $latestIdentifiers);

        $this->applyCriteriaToQueryBuilder($criteria, $qb);
        $qb->groupBy('oi.product, o.customer, o.customerUser, o.website, o.createdAt, oi.productUnitCode, oi.currency');

        return $qb->getQuery()->getArrayResult();
    }

    private function applyCriteriaToQueryBuilder(Criteria $criteria, QueryBuilder $qb): void
    {
        $visitor = new ComparisonExpressionsVisitor();
        $visitor->dispatch($criteria->getWhereExpression());
        $comparisons = $visitor->getComparisons();

        foreach ($comparisons as $comparison) {
            $field = $comparison->getField();
            $value = $comparison->getValue()->getValue();

            if (!isset(self::FIELD_MAPPING[$field])) {
                throw new \InvalidArgumentException(sprintf(
                    'Field "%s" is not mapped to a query alias.',
                    $field
                ));
            }

            $mappedField = self::FIELD_MAPPING[$field];

            switch ($comparison->getOperator()) {
                case Comparison::EQ:
                    QueryBuilderUtil::checkField($field);
                    $qb->andWhere($qb->expr()->eq($mappedField, ":$field"))
                        ->setParameter($field, $value);
                    break;
                case Comparison::IN:
                    QueryBuilderUtil::checkField($field);
                    $qb->andWhere($qb->expr()->in($mappedField, ":$field"))
                        ->setParameter($field, $value);
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Unsupported operator "%s" for field "%s".',
                            $comparison->getOperator(),
                            $field
                        )
                    );
            }
        }
    }

    /**
     * @param Criteria $criteria
     * @param array<int,array<string,string|int|float>> $results
     * @return array<ProductLatestPurchase>
     */
    private function mapToApiModel(Criteria $criteria, array $results): array
    {
        $hierarchicalCustomerIds = $this->extractFilterValues($criteria, 'hierarchicalCustomer') ?? [];
        if (!empty($hierarchicalCustomerIds)) {
            $customerTree = $this->getCustomerTree($hierarchicalCustomerIds);
            $results = $this->filterResults($criteria, $results, $customerTree);
        }

        return $this->mapFilteredResultsToApiModel($results);
    }

    /**
     * @param Criteria $criteria
     * @param array<int,array<string,string|int|float>> $results
     * @param array<int,int[]> $customerTree
     * @return array<int,array<string,string|int|float>>
     */
    private function filterResults(Criteria $criteria, array $results, array $customerTree): array
    {
        $rootCustomers = $this->getRootCustomers($customerTree);

        $criteriaFields = [
            'websiteId' => !empty($this->extractFilterValues($criteria, 'websiteId')),
            'customerUserId' => !empty($this->extractFilterValues($criteria, 'customerUserId')),
            'unit' => !empty($this->extractFilterValues($criteria, 'unit')),
            'currency' => !empty($this->extractFilterValues($criteria, 'currency')),
        ];

        $filteredResults = [];
        $groupedResults = [];

        foreach ($results as $row) {
            $originalCustomerId = (int)$row['customerId'];
            $row['rootCustomerId'] = $this->resolveRootCustomerId($originalCustomerId, $customerTree);

            if (in_array($row['rootCustomerId'], $rootCustomers, true)) {
                $this->groupRootCustomerResults($row, $groupedResults, $criteriaFields);
            } else {
                $filteredResults[] = $row;
            }

            $row['customerId'] = $originalCustomerId;
        }

        foreach ($groupedResults as $rows) {
            usort($rows, static fn ($a, $b) => $b['purchasedAt'] <=> $a['purchasedAt']);
            $filteredResults[] = $rows[0];
        }

        return $filteredResults;
    }

    /**
     * @param array<int,int[]> $customerTree
     * @return int[]
     */
    private function getRootCustomers(array $customerTree): array
    {
        $allChildren = array_merge(...array_values($customerTree));

        return array_diff(array_keys($customerTree), $allChildren);
    }

    private function resolveRootCustomerId(int $customerId, array $customerTree): int
    {
        foreach ($customerTree as $parentId => $children) {
            if ($customerId === $parentId || in_array($customerId, $children)) {
                return $parentId;
            }
        }

        return $customerId;
    }

    private function groupRootCustomerResults(
        array $row,
        array &$groupedResults,
        array $criteriaFields
    ): void {
        $groupingKeyComponents = [
            $row['rootCustomerId'],
            $row['productId']
        ];
        foreach (['websiteId', 'customerUserId', 'unit', 'currency'] as $field) {
            if ($criteriaFields[$field]) {
                $groupingKeyComponents[] = $row[$field];
            }
        }

        $groupingKey = implode('-', $groupingKeyComponents);

        if (!isset($groupedResults[$groupingKey])) {
            $groupedResults[$groupingKey] = [];
        }

        $groupedResults[$groupingKey][] = $row;
    }

    /**
     * @param array<int,array<string,string|int|float>> $filteredResults
     * @return array<ProductLatestPurchase>
     */
    private function mapFilteredResultsToApiModel(array $filteredResults): array
    {
        return array_map(
            static fn ($row) => new ProductLatestPurchase(
                $row['websiteId'],
                $row['customerId'],
                $row['customerUserId'],
                $row['productId'],
                $row['unit'],
                $row['currency'],
                $row['price'],
                $row['purchasedAt']
            ),
            $filteredResults
        );
    }
}

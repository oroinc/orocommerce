<?php

namespace Oro\Bundle\VisibilityBundle\Api\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueException;
use Oro\Bundle\ApiBundle\Filter\ConfigAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FieldFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\VisibilityBundle\Api\VisibilityIdHelper;

/**
 * The filter that is used to filter visibility entities by a value of composite identifier used in API.
 */
class VisibilityIdFilter extends StandaloneFilter implements FieldFilterInterface, ConfigAwareFilterInterface
{
    private VisibilityIdHelper $visibilityIdHelper;
    private EntityDefinitionConfig $config;

    public function setVisibilityIdHelper(VisibilityIdHelper $visibilityIdHelper): void
    {
        $this->visibilityIdHelper = $visibilityIdHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function setConfig(EntityDefinitionConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        if (null !== $value) {
            $criteria->andWhere(
                $this->buildExpression($value->getOperator(), $value->getValue())
            );
        }
    }

    private function buildExpression(?string $operator, mixed $value): Expression
    {
        if (null === $value) {
            throw new \InvalidArgumentException('The value must not be NULL.');
        }

        if (null === $operator) {
            $operator = FilterOperator::EQ;
        }
        if (!\in_array($operator, $this->getSupportedOperators(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'The operator "%s" is not supported.',
                $operator
            ));
        }

        if (\is_array($value)) {
            // a list of identifiers
            if (FilterOperator::NEQ === $operator) {
                // expression: (path1 != value1 OR path2 != value2 OR ...) AND (...)
                // this expression equals to NOT ((path1 = value1 AND path2 = value2 AND ...) OR (...)),
                // but Criteria object does not support NOT expression
                $expressions = [];
                foreach ($value as $val) {
                    $expressions[] = $this->buildNotEqualExpression($this->decodeVisibilityId($val));
                }
                $expr = new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
            } else {
                // expression: (path1 = value1 AND path2 = value2 AND ...) OR (...)
                $expressions = [];
                foreach ($value as $val) {
                    $expressions[] = $this->buildEqualExpression($this->decodeVisibilityId($val));
                }
                $expr = new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
            }
        } else {
            // single identifier
            $visibilityId = $this->decodeVisibilityId($value);
            if (FilterOperator::NEQ === $operator) {
                // expression: path1 != value1 OR path2 != value2 OR ...
                // this expression equals to NOT (path1 = value1 AND path2 = value2 AND ...),
                // but Criteria object does not support NOT expression
                $expr = $this->buildNotEqualExpression($visibilityId);
            } else {
                // expression: path1 = value1 AND path2 = value2 AND ...
                $expr = $this->buildEqualExpression($visibilityId);
            }
        }

        return $expr;
    }

    private function decodeVisibilityId(string $value): array
    {
        $visibilityId = $this->visibilityIdHelper->decodeVisibilityId($value, $this->config->getField('id'));
        if (null === $visibilityId) {
            throw new InvalidFilterValueException(sprintf(
                'The value "%s" is not valid identifier.',
                $value
            ));
        }

        return $visibilityId;
    }

    /**
     * @param array $visibilityId [property path => value, ...]
     *
     * @return Expression
     */
    private function buildEqualExpression(array $visibilityId): Expression
    {
        $expressions = [];
        foreach ($visibilityId as $propertyPath => $value) {
            $expressions[] = Criteria::expr()->eq($this->getAssociationPropertyPath($propertyPath), $value);
        }

        return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
    }

    /**
     * @param array $visibilityId [property path => value, ...]
     *
     * @return Expression
     */
    private function buildNotEqualExpression(array $visibilityId): Expression
    {
        $expressions = [];
        foreach ($visibilityId as $propertyPath => $value) {
            $expressions[] = Criteria::expr()->neq($this->getAssociationPropertyPath($propertyPath), $value);
        }

        return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
    }

    private function getAssociationPropertyPath(string $propertyPath): string
    {
        return substr($propertyPath, 0, strrpos($propertyPath, ConfigUtil::PATH_DELIMITER));
    }
}

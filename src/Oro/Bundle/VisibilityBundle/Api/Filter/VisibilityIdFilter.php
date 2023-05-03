<?php

namespace Oro\Bundle\VisibilityBundle\Api\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueException;
use Oro\Bundle\ApiBundle\Filter\AbstractCompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Filter\ConfigAwareFilterInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\VisibilityBundle\Api\VisibilityIdUtil;

/**
 * The filter that is used to filter visibility entities by a value of composite identifier used in API.
 */
class VisibilityIdFilter extends AbstractCompositeIdentifierFilter implements ConfigAwareFilterInterface
{
    private EntityDefinitionConfig $config;

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
    protected function buildEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $propertyPath => $val) {
            $expressions[] = Criteria::expr()->eq($this->getAssociationPropertyPath($propertyPath), $val);
        }

        return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
    }

    /**
     * {@inheritDoc}
     */
    protected function buildNotEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $propertyPath => $val) {
            $expressions[] = Criteria::expr()->neq($this->getAssociationPropertyPath($propertyPath), $val);
        }

        return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
    }

    /**
     * {@inheritDoc}
     */
    protected function parseIdentifier(mixed $value): mixed
    {
        $visibilityId = VisibilityIdUtil::decodeVisibilityId($value, $this->config->getField('id'));
        if (null === $visibilityId) {
            throw new InvalidFilterValueException(sprintf('The value "%s" is not valid identifier.', $value));
        }

        return $visibilityId;
    }

    private function getAssociationPropertyPath(string $propertyPath): string
    {
        return substr($propertyPath, 0, strrpos($propertyPath, ConfigUtil::PATH_DELIMITER));
    }
}

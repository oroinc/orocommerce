<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\TaxBundle\Entity\TaxRule;

/**
 * Provides a text representation of TaxRule entity.
 */
class TaxRuleEntityNameProvider implements EntityNameProviderInterface
{
    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof TaxRule) {
            return false;
        }

        return sprintf(
            '%s %s %s %s',
            $entity->getTax()->getCode(),
            $entity->getTaxJurisdiction()->getCode(),
            $entity->getProductTaxCode()->getCode(),
            $entity->getCustomerTaxCode()->getCode()
        );
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, TaxRule::class, true)) {
            return false;
        }

        return sprintf(
            '(SELECT CONCAT(%1$s_t.code, \' \', %1$s_j.code, \' \', %1$s_pc.code, \' \', %1$s_cc.code)'
            . ' FROM %2$s %1$s_r INNER JOIN %1$s_r.tax %1$s_t INNER JOIN %1$s_r.taxJurisdiction %1$s_j'
            . ' INNER JOIN %1$s_r.productTaxCode %1$s_pc INNER JOIN %1$s_r.customerTaxCode %1$s_cc'
            . ' WHERE %1$s_r = %1$s)',
            $alias,
            TaxRule::class
        );
    }
}

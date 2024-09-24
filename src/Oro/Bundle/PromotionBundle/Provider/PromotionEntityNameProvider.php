<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\Rule;

/**
 * Provides a text representation of Promotion entity.
 */
class PromotionEntityNameProvider implements EntityNameProviderInterface
{
    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if ($entity instanceof Promotion) {
            return $entity->getRule()->getName();
        }

        return false;
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (is_a($className, Promotion::class, true)) {
            return sprintf(
                '(SELECT %1$s_rule.name FROM %2$s %1$s_rule WHERE %1$s_rule = %1$s.rule)',
                $alias,
                Rule::class
            );
        }

        return false;
    }
}

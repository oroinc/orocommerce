<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Provides a text representation of Consent entity.
 */
class ConsentEntityNameProvider implements EntityNameProviderInterface
{
    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Consent) {
            return false;
        }

        $localizedName = $locale instanceof Localization
            ? (string)$entity->getName($locale)
            : null;

        return $localizedName ?: (string)$entity->getDefaultName();
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, Consent::class, true)) {
            return false;
        }

        if ($locale instanceof Localization) {
            return sprintf(
                'CAST((SELECT COALESCE(%1$s_n.string, %1$s_n.text, %1$s_dn.string, %1$s_dn.text) FROM %2$s %1$s_dn'
                . ' LEFT JOIN %2$s %1$s_n WITH %1$s_n MEMBER OF %1$s.names AND %1$s_n.localization = %3$s'
                . ' WHERE %1$s_dn MEMBER OF %1$s.names AND %1$s_dn.localization IS NULL) AS string)',
                $alias,
                LocalizedFallbackValue::class,
                $locale->getId()
            );
        }

        return sprintf(
            'CAST((SELECT COALESCE(%1$s_n.string, %1$s_n.text) FROM %2$s %1$s_n'
            . ' WHERE %1$s_n MEMBER OF %1$s.names AND %1$s_n.localization IS NULL) AS string)',
            $alias,
            LocalizedFallbackValue::class
        );
    }
}

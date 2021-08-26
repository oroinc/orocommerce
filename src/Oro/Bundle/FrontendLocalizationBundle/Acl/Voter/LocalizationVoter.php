<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Acl\Voter;

use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Prevents removal of localizations that are in use.
 */
class LocalizationVoter extends AbstractEntityVoter
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::DELETE];

    /** @var array */
    private static $usedLocalizationIds;

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return $this->isLocalizationUsed($identifier) ? self::ACCESS_DENIED : self::ACCESS_ABSTAIN;
    }

    private function isLocalizationUsed(int $identifier): bool
    {
        if (null === self::$usedLocalizationIds) {
            $configValues = $this->doctrineHelper->getEntityRepositoryForClass(ConfigValue::class)->findBy([
                'name' => Configuration::DEFAULT_LOCALIZATION,
                'section' => Configuration::ROOT_NAME,
            ]);
            self::$usedLocalizationIds = array_map(
                function (ConfigValue $configValue) {
                    return (int)$configValue->getValue();
                },
                $configValues
            );
        }

        return \in_array($identifier, self::$usedLocalizationIds, true);
    }
}

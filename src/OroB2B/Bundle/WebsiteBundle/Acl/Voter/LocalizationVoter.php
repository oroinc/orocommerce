<?php

namespace OroB2B\Bundle\WebsiteBundle\Acl\Voter;

use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

class LocalizationVoter extends AbstractEntityVoter
{
    /** @var array */
    protected $supportedAttributes = [
        'DELETE'
    ];

    /** @var array */
    static protected $usedLocalizationIds;

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return $this->isLocalizationUsed($identifier) ? self::ACCESS_DENIED : self::ACCESS_ABSTAIN;
    }

    /**
     * @param int $identifier
     * @return bool
     */
    protected function isLocalizationUsed($identifier)
    {
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(ConfigValue::class);
        if (null === self::$usedLocalizationIds) {
            $configValues = $repository->findBy(
                [
                    'name' => Configuration::DEFAULT_LOCALIZATION,
                    'section' => Configuration::ROOT_NAME,
                ]
            );
            self::$usedLocalizationIds = array_map(
                function (ConfigValue $configValue) {
                    return (int) $configValue->getValue();
                },
                $configValues
            );
        }

        return in_array($identifier, self::$usedLocalizationIds, true);
    }
}

<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroB2B\Bundle\AccountBundle\DependencyInjection\Configuration;

class AccountGroupVoter extends AbstractEntityVoter
{
    /**
     * @var array
     */
    protected $supportedAttributes = [
        'DELETE'
    ];

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager($configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        $configKey = Configuration::getSettingName(Configuration::ANONYMOUS_ACCOUNT_GROUP);

        if ($this->configManager && $identifier && $identifier === (int)$this->configManager->get($configKey)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}

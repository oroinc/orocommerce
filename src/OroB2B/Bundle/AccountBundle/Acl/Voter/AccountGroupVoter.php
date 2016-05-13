<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

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
        if ($this->configManager
            && $identifier
            && $this->isAnonymousAccountGroup($identifier)
        ) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param int $identifier
     * @return bool
     */
    protected function isAnonymousAccountGroup($identifier)
    {
        return $identifier === (int)$this->configManager->get('oro_b2b_account.anonymous_account_group');
    }
}

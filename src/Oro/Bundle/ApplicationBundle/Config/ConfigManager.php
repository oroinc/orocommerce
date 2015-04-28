<?php

namespace Oro\Bundle\ApplicationBundle\Config;

class ConfigManager
{
    /**
     * @var array
     */
    protected $config = [
        'oro_b2b_rfp_admin.default_request_status' => 'open',
        'oro_b2b_rfp_admin.default_user_for_notifications' => 'admin@example.com',
        'orob2b_user.allow_frontend_registration' => true,
    ];

    /**
     * @param $configName
     *
     * @return mixed
     */
    public function get($configName)
    {
        return (array_key_exists($configName, $this->config)) ? $this->config[$configName] : null;
    }
}

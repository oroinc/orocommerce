<?php

namespace Oro\Bundle\ApplicationBundle\Config;

class ConfigManager
{
    /**
     * @var array
     */
    protected $config = [
        'oro_b2b_rfp_admin.default_request_status' => 'open',
        'oro_notification.email_notification_sender_email' => 'admin@example.com',
        'oro_notification.email_notification_sender_name' => 'John Dow',
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

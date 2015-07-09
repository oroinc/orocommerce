<?php

namespace OroB2B\Bundle\RFPBundle\Datagrid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class ActionPermissionProvider
{
    /** @var ConfigManager  */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getRequestStatusDefinitionPermissions(ResultRecordInterface $record)
    {
        $isDeleted = $record->getValue('deleted');
        $isDefaultRequestStatus =
            $this->configManager->get('oro_b2b_rfp.default_request_status') == $record->getValue('name');

        return [
            'restore' => $isDeleted,
            'delete'  => !$isDeleted && !$isDefaultRequestStatus,
            'view'    => true,
            'update'  => true
        ];
    }
}

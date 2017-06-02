<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;

/**
 * Handler checks that count of selected products do not exceed limitation and returns product ids from given grid.
 */
class TriggerEventForSelectedProductIdsMassActionHandler implements MassActionHandlerInterface
{
    const FAILED_RESPONSE_MESSAGE = 'oro.product.grid.mass_action.get_selected_product_ids.response.failed';
    const SUCCESS_RESPONSE_MESSAGE = 'oro.product.grid.mass_action.get_selected_product_ids.response.success';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $configKey = Configuration::ROOT_NODE.'.'.Configuration::PRODUCT_COLLECTION_MASS_ACTION_LIMITATION;
        $massActionLimit = $this->configManager->get($configKey);
        $data = $args->getData();
        if (empty($data['force']) && $args->getResults()->count() > $massActionLimit) {
            return new MassActionResponse(false, self::FAILED_RESPONSE_MESSAGE);
        }

        $ids = [];
        foreach ($args->getResults() as $resultRecord) {
            $ids[] = $resultRecord->getValue('id');
        }

        return new MassActionResponse(true, self::SUCCESS_RESPONSE_MESSAGE, ['ids' => $ids]);
    }
}

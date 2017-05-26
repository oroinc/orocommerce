<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;

class GetSelectedProductIdsMassActionHandler implements MassActionHandlerInterface
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
        /** @var Product $product */
        foreach ($args->getResults() as $product) {
            $ids[] = $product->getId();
        }

        return new MassActionResponse(true, self::SUCCESS_RESPONSE_MESSAGE, ['ids' => $ids]);
    }
}

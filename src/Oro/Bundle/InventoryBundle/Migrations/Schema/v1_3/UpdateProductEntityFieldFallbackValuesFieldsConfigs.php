<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigIndexFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Schema\OroProductBundleInstaller;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UpdateProductEntityFieldFallbackValuesFieldsConfigs implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var array */
    private static $fields = [
        'manageInventory',
        'highlightLowInventory',
        'inventoryThreshold',
        'lowInventoryThreshold',
        'minimumQuantityToOrder',
        'maximumQuantityToOrder',
        'decrementQuantity',
        'backOrder',
        'isUpcoming'
    ];

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** @var ExtendOptionsManager $optionsManager */
        $optionsManager = $this->container->get('oro_entity_extend.migration.options_manager');

        foreach (self::$fields as $fieldName) {
            $queries->addQuery(
                new UpdateEntityConfigFieldValueQuery(Product::class, $fieldName, 'importexport', 'full', true)
            );
            $queries->addQuery(
                new UpdateEntityConfigIndexFieldValueQuery(Product::class, $fieldName, 'importexport', 'full', true)
            );

            $options = $optionsManager->getColumnOptions(OroProductBundleInstaller::PRODUCT_TABLE_NAME, $fieldName);
            if ($options && empty($options['importexport']['full'])) {
                $options['importexport']['full'] = true;
                $optionsManager->setColumnOptions(OroProductBundleInstaller::PRODUCT_TABLE_NAME, $fieldName, $options);
            }
        }
    }
}

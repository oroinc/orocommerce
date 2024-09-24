<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigIndexFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class UpdateProductEntityFieldFallbackValuesFieldsConfigs implements Migration, ExtendOptionsManagerAwareInterface
{
    use ExtendOptionsManagerAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $fields = [
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
        foreach ($fields as $fieldName) {
            $queries->addQuery(
                new UpdateEntityConfigFieldValueQuery(Product::class, $fieldName, 'importexport', 'full', true)
            );
            $queries->addQuery(
                new UpdateEntityConfigIndexFieldValueQuery(Product::class, $fieldName, 'importexport', 'full', true)
            );

            $options = $this->extendOptionsManager->getColumnOptions('oro_product', $fieldName);
            if ($options && empty($options['importexport']['full'])) {
                $options['importexport']['full'] = true;
                $this->extendOptionsManager->setColumnOptions('oro_product', $fieldName, $options);
            }
        }
    }
}

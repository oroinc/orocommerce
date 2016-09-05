<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTables implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        $extension->renameTable($schema, $queries, 'orob2b_shipping_freight_class', 'oro_shipping_freight_class');
        $extension->renameTable($schema, $queries, 'orob2b_shipping_length_unit', 'oro_shipping_length_unit');
        $extension->renameTable($schema, $queries, 'orob2b_shipping_orig_warehouse', 'oro_shipping_orig_warehouse');
        $extension->renameTable($schema, $queries, 'orob2b_shipping_product_opts', 'oro_shipping_product_opts');
        $extension->renameTable($schema, $queries, 'orob2b_shipping_weight_unit', 'oro_shipping_weight_unit');

        $schema->getTable('orob2b_shipping_product_opts')
            ->dropIndex('shipping_product_opts__product_id__product_unit_code__uidx');

        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_shipping_product_opts',
            ['product_id', 'product_unit_code'],
            'oro_shipping_product_opts_uidx'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}

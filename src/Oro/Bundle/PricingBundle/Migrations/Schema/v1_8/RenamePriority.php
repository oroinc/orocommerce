<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenamePriority implements Migration, RenameExtensionAwareInterface
{
    const OLD_COLUMN_NAME = 'priority';

    const NEW_COLUMN_NAME = 'sort_order';

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

        $priceListToCusGroup = $schema->getTable('oro_price_list_to_cus_group');
        $priceListToCustomer = $schema->getTable('oro_price_list_to_customer');
        $priceListToWebsite  = $schema->getTable('oro_price_list_to_website');

        if($priceListToCusGroup->hasColumn(self::OLD_COLUMN_NAME)) {
            $extension->renameColumn($schema, $queries, $priceListToCusGroup, 'priority', 'sort_order');
        }

        if( $priceListToCustomer->hasColumn(self::OLD_COLUMN_NAME)) {
            $extension->renameColumn($schema, $queries, $priceListToCustomer, 'priority', 'sort_order');
        }

        if($priceListToWebsite->hasColumn(self::OLD_COLUMN_NAME)) {
            $extension->renameColumn($schema, $queries, $priceListToWebsite, 'priority', 'sort_order');
        }
        
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}


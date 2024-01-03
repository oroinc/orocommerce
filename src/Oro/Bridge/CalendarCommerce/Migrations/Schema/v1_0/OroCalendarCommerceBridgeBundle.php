<?php

namespace Oro\Bridge\CalendarCommerce\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarCommerceBridgeBundle implements Migration, ActivityExtensionAwareInterface
{
    use ActivityExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addCalendarActivityAssociations($schema, $this->activityExtension);
    }

    public static function addCalendarActivityAssociations(Schema $schema, ActivityExtension $activityExtension)
    {
        // no create associations only if calendar bundle is installed
        if ($schema->hasTable('oro_calendar_event')) {
            $legacyAssocTables = [
                'oro_customer_user' => 'orob2b_account_user',
                'oro_order' => 'orob2b_order',
                'oro_rfp_request' => 'orob2b_rfp_request',
                'oro_sale_quote' => 'orob2b_sale_quote',
            ];

            $associationTables = [
                'oro_customer_user',
                'oro_order',
                'oro_rfp_request',
                'oro_sale_quote',
            ];
            foreach ($associationTables as $tableName) {
                if (!$schema->hasTable($tableName)) {
                    $tableName = $legacyAssocTables[$tableName];
                }

                $associationTableName = $activityExtension->getAssociationTableName(
                    'oro_calendar_event',
                    $tableName
                );
                if (!$schema->hasTable($associationTableName)) {
                    $activityExtension->addActivityAssociation($schema, 'oro_calendar_event', $tableName);
                }
            }
        }
    }
}

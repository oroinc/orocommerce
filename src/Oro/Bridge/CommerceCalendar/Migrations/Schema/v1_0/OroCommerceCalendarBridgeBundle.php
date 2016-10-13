<?php

namespace Oro\CommerceCalendarBridgeBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCommerceCalendarBridgeBundle implements Migration, ActivityExtensionAwareInterface
{
    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addCalendarActivityAssociations($schema, $this->activityExtension);
    }

    /**
     * Enable activities
     *
     * @param Schema            $schema
     * @param ActivityExtension $activityExtension
     */
    public static function addCalendarActivityAssociations(Schema $schema, ActivityExtension $activityExtension)
    {
        $legacyAssociationTables = [
            'oro_account_user' => 'orob2b_account_user',
            'oro_order' => 'orob2b_order',
            'oro_rfp_request' => 'orob2b_rfp_request',
            'oro_sale_quote' => 'orob2b_sale_quote',
        ];

        $associationTables = [
            'oro_account_user',
            'oro_order',
            'oro_rfp_request',
            'oro_sale_quote',
        ];

        foreach ($associationTables as $tableName) {
            if (!$schema->hasTable($tableName)) {
                $tableName = $legacyAssociationTables[$tableName];
            }
            $associationTableName = $activityExtension->getAssociationTableName('oro_calendar_event', $tableName);
            if (!$schema->hasTable($associationTableName)) {
                $activityExtension->addActivityAssociation($schema, 'oro_calendar_event', $tableName);
            }
        }
    }
}

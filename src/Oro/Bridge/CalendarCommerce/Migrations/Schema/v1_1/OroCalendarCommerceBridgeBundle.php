<?php

namespace Oro\Bridge\CalendarCommerce\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\InstallerBundle\Migration\UpdateExtendRelationQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarCommerceBridgeBundle implements Migration, RenameExtensionAwareInterface
{
    /** @var RenameExtension */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::renameActivityTables($schema, $queries, $this->renameExtension);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public static function renameActivityTables(Schema $schema, QueryBag $queries, RenameExtension $extension)
    {
        // Execute only if tables were now renamed already

        // CustomerBundle v1_7 - calendar event to account user association
        // MUST be executed before CustomerBundle v1_8 table rename
        if ($schema->hasTable('oro_rel_46a29d19a6adb604a9b8e1')
            && !$schema->hasTable('oro_rel_46a29d19a6adb604aeb863')) {
            $extension->renameTable(
                $schema,
                $queries,
                'oro_rel_46a29d19a6adb604a9b8e1',
                'oro_rel_46a29d19a6adb604aeb863'
            );

            $queries->addQuery(new UpdateExtendRelationQuery(
                'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'Oro\Bundle\AccountBundle\Entity\AccountUser',
                'account_user_489123cf',
                'account_user_795f990e',
                RelationType::MANY_TO_MANY
            ));
        }

        // CustomerBundle v1_8 - calendar event to account user association
        if ($schema->hasTable('oro_rel_46a29d19a6adb604aeb863')
            && !$schema->hasTable('oro_rel_46a29d19a6adb604264ef1')) {
            // rename activity table
            $extension->renameTable(
                $schema,
                $queries,
                'oro_rel_46a29d19a6adb604aeb863',
                'oro_rel_46a29d19a6adb604264ef1'
            );

            $queries->addQuery(new UpdateExtendRelationQuery(
                'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'Oro\Bundle\CustomerBundle\Entity\AccountUser',
                'account_user_795f990e',
                'account_user_741cdecd',
                RelationType::MANY_TO_MANY
            ));

            // rename old activity table
            $queries->addQuery(new UpdateExtendRelationQuery(
                'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'Oro\Bundle\CustomerBundle\Entity\AccountUser',
                'account_user_489123cf',
                'account_user_741cdecd',
                RelationType::MANY_TO_MANY
            ));
        }

        // OrderBundle v1_5 - calendar event to order association
        if ($schema->hasTable('oro_rel_46a29d1934e8bc9c23a92e')) {
            $extension->renameTable(
                $schema,
                $queries,
                'oro_rel_46a29d1934e8bc9c23a92e',
                'oro_rel_46a29d1934e8bc9c2ddbe0'
            );

            $queries->addQuery(new UpdateExtendRelationQuery(
                'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'Oro\Bundle\OrderBundle\Entity\Order',
                'order_19226b65',
                'order_5726bf8f',
                RelationType::MANY_TO_MANY
            ));
        }

        // RFPBundle v1_6 - calendar event to request association
        if ($schema->hasTable('oro_rel_46a29d19f42ab603f15753')) {
            $extension->renameTable(
                $schema,
                $queries,
                'oro_rel_46a29d19f42ab603f15753',
                'oro_rel_46a29d19f42ab603ec4b1d'
            );

            $queries->addQuery(new UpdateExtendRelationQuery(
                'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'Oro\Bundle\RFPBundle\Entity\Request',
                'request_9fd4910b',
                'request_d1d045e1',
                RelationType::MANY_TO_MANY
            ));
        }

        // SaleBundle v1_10 - calendar event to quote association
        if ($schema->hasTable('oro_rel_46a29d19aab0e4f0a0472d')) {
            $extension->renameTable(
                $schema,
                $queries,
                'oro_rel_46a29d19aab0e4f0a0472d',
                'oro_rel_46a29d19aab0e4f0b5ec88'
            );

            $queries->addQuery(new UpdateExtendRelationQuery(
                'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'Oro\Bundle\SaleBundle\Entity\Quote',
                'quote_54b6ea15',
                'quote_54e154f7',
                RelationType::MANY_TO_MANY
            ));
        }
    }
}

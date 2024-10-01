<?php

namespace Oro\Bridge\CalendarCommerce\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bridge\CalendarCommerce\Migrations\Schema\v1_0\OroCalendarCommerceBridgeBundle as CommerceBridgeBundle_v1_0;
use Oro\Bridge\CalendarCommerce\Migrations\Schema\v1_1\OroCalendarCommerceBridgeBundle as CommerceBridgeBundle_v1_1;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarCommerceBridgeBundleInstaller implements
    Installation,
    ActivityExtensionAwareInterface,
    RenameExtensionAwareInterface
{
    use ActivityExtensionAwareTrait;
    use RenameExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_1';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        CommerceBridgeBundle_v1_0::addCalendarActivityAssociations($schema, $this->activityExtension);
        CommerceBridgeBundle_v1_1::renameActivityTables($schema, $queries, $this->renameExtension);
    }
}

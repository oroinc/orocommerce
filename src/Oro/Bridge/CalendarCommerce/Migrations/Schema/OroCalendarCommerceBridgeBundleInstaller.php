<?php

namespace Oro\Bridge\CalendarCommerce\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bridge\CalendarCommerce\Migrations\Schema\v1_0\OroCalendarCommerceBridgeBundle;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarCommerceBridgeBundleInstaller implements Installation, ActivityExtensionAwareInterface
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
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroCalendarCommerceBridgeBundle::addCalendarActivityAssociations($schema, $this->activityExtension);
    }
}

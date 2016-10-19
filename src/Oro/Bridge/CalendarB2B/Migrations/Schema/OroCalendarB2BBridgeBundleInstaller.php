<?php

namespace Oro\Bridge\CalendarB2B\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bridge\CalendarB2B\Migrations\Schema\v1_0\OroCalendarB2BBridgeBundle;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarB2BBridgeBundleInstaller implements Installation, ActivityExtensionAwareInterface
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
        OroCalendarB2BBridgeBundle::addCalendarActivityAssociations($schema, $this->activityExtension);
    }
}

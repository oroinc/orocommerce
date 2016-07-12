<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // email to request association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_26535370f42ab603f15753',
            'oro_rel_26535370f42ab603ec4b1d'
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\EmailBundle\Entity\Email',
            'OroB2B\Bundle\RFPBundle\Entity\Request',
            'request_9fd4910b',
            'request_d1d045e1',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to request association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_46a29d19f42ab603f15753',
            'oro_rel_46a29d19f42ab603ec4b1d'
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\CalendarBundle\Entity\CalendarEvent',
            'OroB2B\Bundle\RFPBundle\Entity\Request',
            'request_9fd4910b',
            'request_d1d045e1',
            RelationType::MANY_TO_MANY
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}

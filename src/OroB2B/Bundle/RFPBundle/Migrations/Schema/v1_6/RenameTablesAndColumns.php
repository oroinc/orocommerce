<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\FrontendBundle\Migration\RemoveExtendRelationQuery;

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
        $queries->addQuery(new RemoveExtendRelationQuery(
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\RFPBundle\Entity\Request',
            'request_9fd4910b',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to request association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_46a29d19f42ab603f15753',
            'oro_rel_46a29d19f42ab603ec4b1d'
        );
        $queries->addQuery(new RemoveExtendRelationQuery(
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\RFPBundle\Entity\Request',
            'request_9fd4910b',
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

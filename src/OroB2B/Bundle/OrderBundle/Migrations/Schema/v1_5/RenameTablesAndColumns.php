<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_5;

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
        // email to order association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_2653537034e8bc9c23a92e',
            'oro_rel_2653537034e8bc9c2ddbe0'
        );
        $queries->addQuery(new RemoveExtendRelationQuery(
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\OrderBundle\Entity\Order',
            'order_19226b65',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to order association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_46a29d1934e8bc9c23a92e',
            'oro_rel_46a29d1934e8bc9c2ddbe0'
        );
        $queries->addQuery(new RemoveExtendRelationQuery(
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\OrderBundle\Entity\Order',
            'order_19226b65',
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

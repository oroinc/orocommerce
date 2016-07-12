<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_6;

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
        // email to account user association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_26535370a6adb604a9b8e1',
            'oro_rel_26535370a6adb604aeb863'
        );
        $queries->addQuery(new RemoveExtendRelationQuery(
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\AccountBundle\Entity\AccountUser',
            'account_user_489123cf',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to account user association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_46a29d19a6adb604a9b8e1',
            'oro_rel_46a29d19a6adb604aeb863'
        );
        $queries->addQuery(new RemoveExtendRelationQuery(
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\AccountBundle\Entity\AccountUser',
            'account_user_489123cf',
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

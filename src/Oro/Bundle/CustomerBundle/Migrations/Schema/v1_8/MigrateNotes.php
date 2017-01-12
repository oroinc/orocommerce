<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindForRenamedEntitiesMigration;

class MigrateNotes extends UpdateNoteAssociationKindForRenamedEntitiesMigration
{
    protected $entitiesNames = [
        'Customer',
        'CustomerGroup',
        'CustomerAddress',
        'CustomerUserSettings',
        'AccountUserRole',
        'CustomerUser',
        'CustomerUserAddress',
        'ProductVisibility',
        'AccountProductVisibility',
        'CategoryVisibility',
        'CustomerCategoryVisibility',
        'CustomerGroupProductVisibility',
        'CustomerGroupCategoryVisibility',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getRenamedEntitiesNames(Schema $schema)
    {
        $b2bNameSpace = 'OroB2B\Bundle\AccountBundle\Entity';
        $oroAccountNameSpace = 'Oro\Bundle\AccountBundle\Entity';
        $newNameSpace = 'Oro\Bundle\CustomerBundle\Entity';

        $noteTable = $schema->getTable('oro_note');
        $renamedEntityNamesMapping = [];
        foreach ($this->entitiesNames as $entityName) {
            $oldClassName = "$b2bNameSpace\\$entityName";
            if (!$noteTable->hasColumn($this->getNoteAssociationColumnName($oldClassName))) {
                $oldClassName = "$oroAccountNameSpace\\$entityName";
            }

            $renamedEntityNamesMapping["$newNameSpace\\$entityName"] = $oldClassName;
        }

        return $renamedEntityNamesMapping;
    }

    /**
     * @param string $targetClass
     *
     * @return string
     */
    protected function getNoteAssociationColumnName($targetClass)
    {
        $noteAssociationName = ExtendHelper::buildAssociationName($targetClass);
        $noteAssociationColumnName = $this->nameGenerator->generateRelationColumnName($noteAssociationName);

        return $noteAssociationColumnName;
    }
}

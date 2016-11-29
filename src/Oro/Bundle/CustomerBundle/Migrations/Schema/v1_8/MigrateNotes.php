<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindForRenamedEntitiesMigration;

class MigrateNotes extends UpdateNoteAssociationKindForRenamedEntitiesMigration
{
    /**
     * {@inheritdoc}
     */
    protected function getRenamedEntitiesNames(Schema $schema)
    {
        return [
            'Oro\Bundle\CustomerBundle\Entity\Account' => 'OroB2B\Bundle\AccountBundle\Entity\Account',
            'Oro\Bundle\CustomerBundle\Entity\AccountAddress' => 'OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
            'Oro\Bundle\CustomerBundle\Entity\AccountGroup' => 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup',
            'Oro\Bundle\CustomerBundle\Entity\AccountUserSettings' => 'OroB2B\Bundle\AccountBundle\Entity' .
                '\AccountUserSettings',
            'Oro\Bundle\CustomerBundle\Entity\AccountUserRole' => 'OroB2B\Bundle\AccountBundle\Entity\AccountUserRole',
            'Oro\Bundle\CustomerBundle\Entity\AccountUser' => 'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
            'Oro\Bundle\CustomerBundle\Entity\AccountUserAddress' => 'OroB2B\Bundle\AccountBundle\Entity' .
                '\AccountUserAddress',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility' => 'OroB2B\Bundle\AccountBundle' .
                '\Entity\Visibility\ProductVisibility',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility' => 'OroB2B\Bundle\AccountBundle' .
                '\Entity\Visibility\AccountProductVisibility',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility' => 'OroB2B\Bundle\AccountBundle' .
                '\Entity\Visibility\CategoryVisibility',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility' => 'OroB2B\Bundle\AccountBundle' .
                '\Entity\Visibility\AccountCategoryVisibility',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility' => 'OroB2B\Bundle' .
                '\AccountBundle\Entity\Visibility\AccountGroupProductVisibility',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility' => 'OroB2B\Bundle' .
                '\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility',
        ];
    }
}

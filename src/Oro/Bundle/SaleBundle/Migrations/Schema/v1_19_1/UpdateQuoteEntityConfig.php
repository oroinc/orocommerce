<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_19_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateQuoteEntityConfig implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /**
         * This configuration currently could not be changed from the UI, so we could
         * replace it without a merge with already defined configuration
         * (At the current moment only field level configuration is changeable)
         */
        $query = new UpdateEntityConfigEntityValueQuery(
            'Oro\Bundle\SaleBundle\Entity\Quote',
            'entity',
            'contact_information',
            [
                'email' => [
                    [
                        'fieldName' => 'contactInformation'
                    ]
                ]
            ]
        );

        $queries->addPostQuery($query);
    }
}

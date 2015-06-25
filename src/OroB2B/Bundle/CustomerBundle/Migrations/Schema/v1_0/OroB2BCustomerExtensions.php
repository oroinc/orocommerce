<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;


class OroB2BCustomerExtensions implements Migration, ExtendExtensionAwareInterface
{
    const TABLE_NAME = 'orob2b_customer';

    /** @var  ExtendExtension */
    protected $extendExtension;

    /**
     * Sets the ExtendExtension
     *
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Modifies the given schema to apply necessary changes of a database
     * The given query bag can be used to apply additional SQL queries before and after schema changes
     *
     * @param Schema   $schema
     * @param QueryBag $queries
     * @return void
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addEnumField($schema);
    }

    private function addEnumField(Schema $schema)
    {
        $this->extendExtension->addEnumField(
            $schema,
            static::TABLE_NAME,
            'internal_rating',
            'cust_internal_rating'
        );
    }


}
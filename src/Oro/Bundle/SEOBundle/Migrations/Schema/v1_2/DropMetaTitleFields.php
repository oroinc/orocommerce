<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class DropMetaTitleFields implements
    Migration,
    OrderedMigrationInterface,
    NameGeneratorAwareInterface
{
    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }
    
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new DropMetaTitleFieldsQuery($this->nameGenerator));
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
}

<?php

namespace Oro\Bundle\FrontendBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DatabaseSchemaTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testTableAndSequenceNames()
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->getContainer()->get('doctrine');
        $testedConnections = [];

        /** @var Connection $connection */
        foreach ($registry->getConnections() as $connection) {
            $connectionIdentifier = $this->getConnectionIdentifier($connection);
            if (in_array($connectionIdentifier, $testedConnections, true)) {
                continue;
            }

            $schemaManager = $connection->getSchemaManager();
            $this->assertSchema($schemaManager->createSchema());
            $testedConnections[] = $connectionIdentifier;
        }
    }

    /**
     * @param Connection $connection
     * @return string
     */
    protected function getConnectionIdentifier(Connection $connection)
    {
        return md5(json_encode($connection->getParams()));
    }

    /**
     * @param Schema $schema
     */
    protected function assertSchema(Schema $schema)
    {
        $tableNames = array_map(
            function (Table $table) {
                return $table->getName();
            },
            $schema->getTables()
        );
        $incorrectTableNames = array_filter(
            $tableNames,
            function ($name) {
                return strpos($name, 'orob2b') === 0;
            }
        );
        $this->assertEmpty(
            $incorrectTableNames,
            'Incorrect table names: ' . implode(', ', $incorrectTableNames)
        );

        $sequenceNames = array_map(
            function (Sequence $sequence) {
                return $sequence->getName();
            },
            $schema->getSequences()
        );
        $incorrectSequenceNames = array_filter(
            $sequenceNames,
            function ($name) {
                return strpos($name, 'orob2b') === 0;
            }
        );
        $this->assertEmpty(
            $incorrectSequenceNames,
            'Incorrect sequence names: ' . implode(', ', $incorrectSequenceNames)
        );
    }
}

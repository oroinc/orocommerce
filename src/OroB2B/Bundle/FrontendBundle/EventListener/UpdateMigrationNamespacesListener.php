<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Doctrine\DBAL\Connection;

use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;

/**
 * Change namespace for all loaded migrations and fixtures
 *
 * TODO: remove this listener after stable release
 */
class UpdateMigrationNamespacesListener
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param PreMigrationEvent $event
     */
    public function preUp(PreMigrationEvent $event)
    {
        $migrations = $event->getData("SELECT id, bundle FROM oro_migrations WHERE bundle LIKE 'OroB2B%'");
        foreach ($migrations as $migration) {
            $id = $migration['id'];
            $bundle = $migration['bundle'];
            $bundle = preg_replace('/^OroB2B/', 'Oro', $bundle, 1);
            $this->connection->executeQuery(
                'UPDATE oro_migrations SET bundle = ? WHERE id = ?',
                [$bundle, $id]
            );
        }

        $fixtures = $event->getData("SELECT id, class_name FROM oro_migrations_data WHERE class_name LIKE 'OroB2B%'");
        foreach ($fixtures as $fixture) {
            $id = $fixture['id'];
            $className = $fixture['class_name'];
            $className = str_replace('OroB2B', 'Oro', $className);

            $this->connection->executeQuery(
                'UPDATE oro_migrations_data SET class_name = ? WHERE id = ?',
                [$className, $id]
            );
        }
    }
}

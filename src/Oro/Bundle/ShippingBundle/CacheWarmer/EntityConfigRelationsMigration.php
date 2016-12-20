<?php

namespace Oro\Bundle\ShippingBundle\CacheWarmer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveManyToManyRelationQuery;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\NoteBundle\Entity\Note;
use Psr\Log\LoggerInterface;

class EntityConfigRelationsMigration
{
    /**
     * @internal
     */
    const SHIPPING_RULE_CLASS_NAME = 'Oro\Bundle\ShippingBundle\Entity\ShippingRule';

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $applicationInstalled;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param LoggerInterface $logger
     * @param bool $applicationInstalled
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger,
        $applicationInstalled
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger;
        $this->applicationInstalled = (bool)$applicationInstalled;
    }

    public function migrate()
    {
        if (!$this->applicationInstalled) {
            return;
        }

        if (class_exists(static::SHIPPING_RULE_CLASS_NAME)) {
            return;
        }

        /** @var Connection $configConnection */
        $configConnection = $this->managerRegistry->getConnection('config');
        $tables = $configConnection->getSchemaManager()->listTableNames();
        if (!in_array('oro_entity_config', $tables, true)) {
            return;
        }

        $targetEntityClassName = static::SHIPPING_RULE_CLASS_NAME;

        $activityListAssociationName = ExtendHelper::buildAssociationName(
            $targetEntityClassName,
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );

        $noteAssociationName = ExtendHelper::buildAssociationName(
            $targetEntityClassName,
            ActivityScope::ASSOCIATION_KIND
        );

        $this->executeUpdateRelationsQuery(
            new RemoveManyToManyRelationQuery(Note::class, $noteAssociationName),
            $configConnection
        );
        $this->executeUpdateRelationsQuery(
            new RemoveManyToManyRelationQuery(ActivityList::class, $activityListAssociationName),
            $configConnection
        );

        $this->executeUpdateRelationsQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class_name',
                ['class_name' => $targetEntityClassName],
                ['class_name' => Type::STRING]
            ),
            $configConnection
        );
    }

    /**
     * @param ParametrizedMigrationQuery $query
     * @param Connection $connection
     */
    protected function executeUpdateRelationsQuery(ParametrizedMigrationQuery $query, Connection $connection)
    {
        $query->setConnection($connection);
        $query->execute($this->logger);
    }
}

<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Query\QueryBuilder;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\SyncBundle\Tests\Unit\Wamp\PDO;

class InsertDefaultLocalizationTitleQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return ['Adds default titles to migrated (untitled) Localizations'];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->connection->createQueryBuilder();
        $localizations = $qb->select('l.id, l.name, lt.localized_value_id')
            ->from('oro_localization', 'l')
            ->leftJoin('l', 'oro_localization_title', 'lt', 'lt.localization_id = l.id')
            ->andWhere($qb->expr()->isNull('localized_value_id'))
            ->orderBy('id')
            ->execute()
            ->fetchAll(PDO::FETCH_OBJ);

        foreach ($localizations as $localization) {
            $sql = sprintf("INSERT INTO oro_fallback_localization_val (string) VALUES ('%s')", $localization->name);

            $this->connection->exec($sql);
            $this->logQuery($logger, $sql);

            $fallbackLocalizationValueId = (int)$this->connection
                ->executeQuery('SELECT MAX(id) FROM oro_fallback_localization_val')
                ->fetchColumn();

            $sql = sprintf(
                'INSERT INTO %s (localization_id, localized_value_id) VALUES (%d, %d)',
                'oro_localization_title',
                $localization->id,
                $fallbackLocalizationValueId
            );

            $this->connection->exec($sql);
            $this->logQuery($logger, $sql);
        }
    }
}

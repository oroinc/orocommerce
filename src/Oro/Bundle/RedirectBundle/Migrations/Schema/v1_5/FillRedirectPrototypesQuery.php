<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Fill prototype fields in redirect table.
 */
class FillRedirectPrototypesQuery extends ParametrizedMigrationQuery
{
    private const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    private function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        list($start, $end) = $this->getScope($logger);

        $this->updatePrototype($logger, $start, $end, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param int $start
     * @param int $end
     * @param bool $dryRun
     */
    private function updatePrototype(LoggerInterface $logger, int $start, int $end, $dryRun)
    {
        $query = "UPDATE oro_redirect r
          SET 
            redirect_from_prototype = CASE
            WHEN
            REVERSE(SUBSTR(REVERSE(r.redirect_from), 1, POSITION('/' IN SUBSTR(REVERSE(r.redirect_from), 1)) - 1)) = ''
            THEN NULL
            ELSE REVERSE(SUBSTR(REVERSE(r.redirect_from), 1, POSITION('/' IN SUBSTR(REVERSE(r.redirect_from), 1)) - 1))
            END,
            redirect_to_prototype = (SELECT rs.slug_prototype FROM oro_redirect_slug rs WHERE rs.id = r.slug_id)
          WHERE redirect_to_prototype IS NULL AND r.id BETWEEN :start AND :end";

        $this->executeQuery($logger, $query, $start, $end, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param string $query
     * @param int $start
     * @param int $end
     * @param bool $dryRun
     */
    private function executeQuery(LoggerInterface $logger, string $query, int $start, int $end, $dryRun)
    {
        $types = ['start' => Types::INTEGER, 'end' => Types::INTEGER];

        while ($start <= $end) {
            $params = ['start' => $start, 'end' => $end];

            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
            }

            $start += self::BATCH_SIZE;
        }
    }

    private function getScope(LoggerInterface $logger): array
    {
        $query = 'SELECT MIN(id) AS start, MAX(id) AS end FROM oro_redirect LIMIT 1';

        $this->logQuery($logger, $query);

        $data = $this->connection->fetchAssoc($query);

        return [$data['start'] ?? 0, $data['end'] ?? 0];
    }
}

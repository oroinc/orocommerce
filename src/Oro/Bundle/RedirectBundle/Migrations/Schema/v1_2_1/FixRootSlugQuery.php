<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_2_1;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class FixRootSlugQuery extends ParametrizedMigrationQuery
{
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
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $url = '/';
        $urlHash = md5($url);

        $deleteQuery = 'DELETE FROM oro_redirect_slug 
          WHERE 
            id NOT IN (SELECT slug_id FROM oro_slug_scope) 
            AND url = :url 
            AND url_hash = :url_hash';

        $parameters = [
            'url' => $url,
            'url_hash' => $urlHash
        ];
        $types = [
            'url' => Types::STRING,
            'url_hash' => Types::STRING
        ];

        $this->logQuery($logger, $deleteQuery, $parameters, $types);
        if (!$dryRun) {
            $this->connection->executeQuery($deleteQuery, $parameters, $types);
        }
    }
}

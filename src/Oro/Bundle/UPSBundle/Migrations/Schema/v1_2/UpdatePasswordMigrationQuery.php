<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This migration encrypts existing UPS api passwords in DB
 */
class UpdatePasswordMigrationQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * UpdatePasswordMigrationQuery constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return 'Add Api password encryption';
    }

    /**
     * @inheritDoc
     */
    public function execute(LoggerInterface $logger)
    {
        /**
         * @var SymmetricCrypterInterface $encryptor
         */
        $encryptor = $this->container->get('oro_security.encoder.default');

        $getIntegrationsSQL = "SELECT id, ups_api_password FROM oro_integration_transport WHERE type = 'upstransport'";

        $integrations = $this->connection->fetchAll($getIntegrationsSQL);

        foreach ($integrations as $integration) {
            $encrypterPassword = $encryptor->encryptData($integration['ups_api_password']);

            $updateSQL = sprintf(
                "UPDATE oro_integration_transport SET ups_api_password = '%s' WHERE id = %s",
                $encrypterPassword,
                $integration['id']
            );

            $this->connection->exec($updateSQL);
        }
    }
}

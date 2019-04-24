<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migration\ReEncryptMigrationQuery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ChangeCrypterForPayPalSetting implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const RE_ENCRYPTED_COLUMNS = [
        'pp_vendor', 'pp_partner', 'pp_user', 'pp_password', 'pp_proxy_host', 'pp_proxy_port'
    ];

    private const INTEGRATION_TABLE = 'oro_integration_transport';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new ChangeColumnTypeToCryptedStringQuery(
            self::INTEGRATION_TABLE,
            self::RE_ENCRYPTED_COLUMNS
        ));

        $queries->addPostQuery(
            new ReEncryptMigrationQuery(
                $this->container->get('oro_security.encoder.default'),
                $this->container->get('oro_security.encoder.repetitive_crypter'),
                self::INTEGRATION_TABLE,
                self::RE_ENCRYPTED_COLUMNS
            )
        );
    }
}

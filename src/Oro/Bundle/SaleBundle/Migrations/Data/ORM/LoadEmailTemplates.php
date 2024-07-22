<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates.
 */
class LoadEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroSaleBundle/Migrations/Data/ORM/emails');
    }

    public function getVersion(): string
    {
        return '1.2';
    }

    protected function getEmailHashesToUpdate(): array
    {
        return [
            'quote_email_link' => [
                '1c46e5b37f621f89480849737710b2df', // 1.0
                '591a234bf39e59bb69cf9f72466b4a94', // 1.1
                '9d145790944f123794e746835575245b', // 1.2
            ],
        ];
    }
}

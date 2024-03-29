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
        return '1.1';
    }

    protected function getEmailHashesToUpdate(): array
    {
        return [
            'quote_email_link' => ['1c46e5b37f621f89480849737710b2df'], // 1.0
        ];
    }
}

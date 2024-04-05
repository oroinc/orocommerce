<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates.
 */
class LoadGuestAccessEmailTemplate extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroSaleBundle/Migrations/Data/ORM/guest_access_email');
    }

    public function getVersion(): string
    {
        return '1.1';
    }

    protected function getEmailHashesToUpdate(): array
    {
        return [
            'quote_email_link_guest' => ['6b14248b184799bf3849141ec800ff5a'], // 1.0
        ];
    }
}

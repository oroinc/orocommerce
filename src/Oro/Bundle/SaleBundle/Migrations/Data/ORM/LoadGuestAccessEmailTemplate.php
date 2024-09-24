<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates.
 */
class LoadGuestAccessEmailTemplate extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroSaleBundle/Migrations/Data/ORM/guest_access_email');
    }

    #[\Override]
    public function getVersion(): string
    {
        return '1.2';
    }

    #[\Override]
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'quote_email_link_guest' => [
                '6b14248b184799bf3849141ec800ff5a', // 1.0
                '20e048050b348dae27b09e99b1e3005b', // 1.1
                '20e048050b348dae27b09e99b1e3005b', // 1.2
            ],
        ];
    }
}

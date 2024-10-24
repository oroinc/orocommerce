<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates for RFP entity.
 */
class LoadEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    #[\Override]
    public function getVersion(): string
    {
        return '1.4';
    }

    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroRFPBundle/Migrations/Data/ORM/data/emails/request');
    }

    #[\Override]
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'request_create_confirmation' => [
                '8728cf6b2cb34845f1f2bb65aad21769', // 1.0
                '674127291ed7a18b4d3bb9e288a10db0', // 1.1
                'ea205dc877d4587ec786d689a7c63364', // 1.2
                '76f2c004315ea55f73844b05b8177f0a', // 1.3
                '5bd5253d8f2f115f952dfb3e904acce7', // 1.4
            ],
            'request_create_notification' => [
                '812419cdd5af1d4d753059e93c58f98e', // 1.0
                'f860d5ce5bc2d984ca150c8247fbbfdb', // 1.1
            ],
        ];
    }
}

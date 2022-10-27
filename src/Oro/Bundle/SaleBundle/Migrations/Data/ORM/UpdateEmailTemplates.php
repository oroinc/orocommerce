<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Updates email templates to new version matching old versions available for update by hashes
 */
class UpdateEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'quote_accepted' => ['ea2d3227512ab2dfc58c23fa0a55dc8b'],
            'quote_approved' => ['ee48430036bb35985a0ec27ed6505574'],
            'quote_approved_and_sent_to_customer' => ['b55fc3eabeb44c49e894c8a1ed3a2200'],
            'quote_cancelled' => ['31da01106d12063f4210733cb051eda1'],
            'quote_created' => ['23a17455bfaa05dbc6bfdffbbfc6079e'],
            'quote_declined' => ['79bfd4c920f8c6d402841e8c6a8bce00'],
            'quote_declined_by_customer' => ['4029adf5442640e64520d12c197e7469'],
            'quote_deleted' => ['90a60960ad0c07ceac49c5ec2d37007a'],
            'quote_edited' => ['96e6feb92a58aff58a852d51c259d85e'],
            'quote_expired_automatic' => ['5c085af89f8a859f7648e8407eaec80e'],
            'quote_expired_manual' => ['64714211ae568461d9831abd2ccc4700'],
            'quote_not_approved' => ['6c44f4dd7460f7b83cbb94cb9a46968c'],
            'quote_reopened' => ['55d1b3264d8e04e02e13f617b3ab3281'],
            'quote_restored' => ['9e226a1b7db033eecc7cc9e76809ca59'],
            'quote_sent_to_customer' => ['b9a53e3afbfa44fad7633e4bf1024d53'],
            'quote_submitted_for_review' => ['d10131fccd9b822bbb6c7f4b98fbf84e'],
            'quote_under_review' => ['9c252556de132abd466e8b215141a4b2'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroSaleBundle/Migrations/Data/ORM/email_notifications');
    }
}

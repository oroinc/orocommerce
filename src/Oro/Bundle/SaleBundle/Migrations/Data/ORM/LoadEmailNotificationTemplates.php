<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates.
 */
class LoadEmailNotificationTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroSaleBundle/Migrations/Data/ORM/email_notifications');
    }

    public function getVersion(): string
    {
        return '1.2';
    }

    protected function getEmailHashesToUpdate(): array
    {
        return [
            'quote_accepted' => [
                'ea2d3227512ab2dfc58c23fa0a55dc8b', // 1.0
                'fe53025650635b96652c7b389c7876fe', // 1.1
            ],
            'quote_approved' => [
                'ee48430036bb35985a0ec27ed6505574', // 1.0
                '4175204eafa8b35241747e462aa74a21', // 1.1
            ],
            'quote_approved_and_sent_to_customer' => [
                'b55fc3eabeb44c49e894c8a1ed3a2200', // 1.0
                '7c945454c88848b9c28c1422077a9a1b', // 1.1
            ],
            'quote_cancelled' => [
                '31da01106d12063f4210733cb051eda1', // 1.0
                '7259f31212042e900fb826aa6dd73bba', // 1.1
            ],
            'quote_created' => [
                '23a17455bfaa05dbc6bfdffbbfc6079e', // 1.0
                'dea09c1b373e325a22b0c27a2cb245ab', // 1.1
            ],
            'quote_declined' => [
                '79bfd4c920f8c6d402841e8c6a8bce00', // 1.0
                '76064642d4fae11a16aa70df365cd7c5', // 1.1
            ],
            'quote_declined_by_customer' => [
                '4029adf5442640e64520d12c197e7469', // 1.0
                '8510b50948124dd43bc74e74de43013b', // 1.1
            ],
            'quote_deleted' => [
                '90a60960ad0c07ceac49c5ec2d37007a', // 1.0
                'c05dfcad82c2bde96a6df9bbec0eecad', // 1.1
            ],
            'quote_edited' => [
                '96e6feb92a58aff58a852d51c259d85e', // 1.0
                'af69f810d0629c1a7502accc13c27f39', // 1.1
            ],
            'quote_expired_automatic' => [
                '5c085af89f8a859f7648e8407eaec80e', // 1.0
                'e5b397cd1251ced4125e13354f6a007b', // 1.1
            ],
            'quote_expired_manual' => [
                '64714211ae568461d9831abd2ccc4700', // 1.0
                '052fbabfc5434171f67a252fbb769640', // 1.1
            ],
            'quote_not_approved' => [
                '6c44f4dd7460f7b83cbb94cb9a46968c', // 1.0
                '4d2109613e2567b3bfb8664fdb4824f5', // 1.1
            ],
            'quote_reopened' => [
                '55d1b3264d8e04e02e13f617b3ab3281', // 1.0
                '7a8b3988708dd2f2fde349db49a33089', // 1.1
            ],
            'quote_restored' => [
                '9e226a1b7db033eecc7cc9e76809ca59', // 1.0
                'c9266f6d662e52bb16da9e81ff3f3445', // 1.1
            ],
            'quote_sent_to_customer' => [
                'b9a53e3afbfa44fad7633e4bf1024d53', // 1.0
                'd0e9bb82779baba9e8791afab206c6f8', // 1.1
            ],
            'quote_submitted_for_review' => [
                'd10131fccd9b822bbb6c7f4b98fbf84e', // 1.0
                'f4bab4697ac2456703cc53e71ad81873', // 1.1
            ],
            'quote_under_review' => [
                '9c252556de132abd466e8b215141a4b2', // 1.0
                '5253e4cfae25ae873f06c26ab149b7d4', // 1.1
            ],
        ];
    }
}

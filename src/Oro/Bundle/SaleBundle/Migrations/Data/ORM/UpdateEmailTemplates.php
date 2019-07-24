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
            'quote_accepted' => ['fe53025650635b96652c7b389c7876fe'],
            'quote_approved' => ['4175204eafa8b35241747e462aa74a21'],
            'quote_approved_and_sent_to_customer' => ['7c945454c88848b9c28c1422077a9a1b'],
            'quote_cancelled' => ['7259f31212042e900fb826aa6dd73bba'],
            'quote_created' => ['dea09c1b373e325a22b0c27a2cb245ab'],
            'quote_declined' => ['76064642d4fae11a16aa70df365cd7c5'],
            'quote_declined_by_customer' => ['8510b50948124dd43bc74e74de43013b'],
            'quote_deleted' => ['c05dfcad82c2bde96a6df9bbec0eecad'],
            'quote_edited' => ['af69f810d0629c1a7502accc13c27f39'],
            'quote_expired_automatic' => ['e5b397cd1251ced4125e13354f6a007b'],
            'quote_expired_manual' => ['052fbabfc5434171f67a252fbb769640'],
            'quote_not_approved' => ['4d2109613e2567b3bfb8664fdb4824f5'],
            'quote_reopened' => ['7a8b3988708dd2f2fde349db49a33089'],
            'quote_restored' => ['c9266f6d662e52bb16da9e81ff3f3445'],
            'quote_sent_to_customer' => ['d0e9bb82779baba9e8791afab206c6f8'],
            'quote_submitted_for_review' => ['f4bab4697ac2456703cc53e71ad81873'],
            'quote_under_review' => ['5253e4cfae25ae873f06c26ab149b7d4'],
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

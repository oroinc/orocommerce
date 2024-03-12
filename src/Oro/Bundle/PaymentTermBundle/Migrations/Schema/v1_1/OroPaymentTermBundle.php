<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPaymentTermBundle implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new RemoveFieldQuery('Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'accounts')
        );

        $queries->addPostQuery(
            new RemoveFieldQuery('Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm', 'accountGroups')
        );

        if ($schema->hasTable('oro_payment_term_to_account')) {
            $queries->addPostQuery($this->getAccountQuery());
            $queries->addPostQuery('DROP TABLE oro_payment_term_to_account;');
        }

        if ($schema->hasTable('oro_payment_term_to_acc_grp')) {
            $queries->addPostQuery($this->getGroupQuery());
            $queries->addPostQuery('DROP TABLE oro_payment_term_to_acc_grp;');
        }
    }

    private function getAccountQuery(): string
    {
        if ($this->platform instanceof PostgreSqlPlatform) {
            return <<<QUERY
UPDATE oro_customer a
SET payment_term_7c4f1e8e_id = pta.payment_term_id
FROM oro_payment_term_to_account pta
WHERE pta.account_id = a.id AND a.payment_term_7c4f1e8e_id IS NULL;
QUERY;
        }
        if ($this->platform instanceof MySqlPlatform) {
            return <<<QUERY
UPDATE oro_customer a
JOIN oro_payment_term_to_account pta ON pta.account_id = a.id
SET a.payment_term_7c4f1e8e_id = pta.payment_term_id
WHERE a.payment_term_7c4f1e8e_id IS NULL;
QUERY;
        }

        throw new \RuntimeException('Unsupported platform ');
    }

    private function getGroupQuery(): string
    {
        if ($this->platform instanceof PostgreSqlPlatform) {
            return <<<QUERY
UPDATE oro_customer_group ag
SET payment_term_7c4f1e8e_id = ptag.payment_term_id
FROM oro_payment_term_to_acc_grp ptag
WHERE ptag.account_group_id = ag.id AND ag.payment_term_7c4f1e8e_id IS NULL;
QUERY;
        }
        if ($this->platform instanceof MySqlPlatform) {
            return <<<QUERY
UPDATE oro_customer_group ag
JOIN oro_payment_term_to_acc_grp ptag ON ptag.account_group_id = ag.id
SET ag.payment_term_7c4f1e8e_id = ptag.payment_term_id;
WHERE ag.payment_term_7c4f1e8e_id IS NULL;
QUERY;
        }

        throw new \RuntimeException('Unsupported platform ');
    }
}

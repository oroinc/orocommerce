<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Schema\v1_2_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types\CryptedTextType;

/**
 * Converts PayPal encrypted credential columns from crypted_string (VARCHAR(255)) to crypted_text (TEXT) to fit
 * AES-encrypted values that exceed 255 chars.
 */
class ChangeEncryptedColumnsToCryptedText implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_integration_transport');

        $this->changeColumnToCryptedText($table, 'pp_partner');
        $this->changeColumnToCryptedText($table, 'pp_vendor');
        $this->changeColumnToCryptedText($table, 'pp_user');
        $this->changeColumnToCryptedText($table, 'pp_password');
        $this->changeColumnToCryptedText($table, 'pp_proxy_host');
        $this->changeColumnToCryptedText($table, 'pp_proxy_port');
    }

    private function changeColumnToCryptedText(Table $table, string $columnName): void
    {
        if ($table->getColumn($columnName)->getType()->getName() === CryptedTextType::TYPE) {
            return;
        }

        $table->modifyColumn($columnName, [
            'type' => Type::getType(CryptedTextType::TYPE),
            'length' => null,
            'comment' => '(DC2Type:' . CryptedTextType::TYPE . ')',
        ]);
    }
}

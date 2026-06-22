<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v7_1_0_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EmailBundle\Migration\SetEmailAvailableInTemplateQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Disables fields that should not be available in email templates.
 */
class DisableFieldsInEmailTemplates implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(
            new SetEmailAvailableInTemplateQuery(
                entityClass: PaymentTransaction::class,
                availableInTemplate: false,
                fieldNames: ['accessIdentifier'],
                immutable: true
            )
        );
    }
}

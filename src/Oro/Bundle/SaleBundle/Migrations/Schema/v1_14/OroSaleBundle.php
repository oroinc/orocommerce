<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Migrations\Data\ORM\LoadQuoteCustomerStatuses;
use Oro\Bundle\SaleBundle\Migrations\Data\ORM\LoadQuoteInternalStatuses;
use Oro\Bundle\TranslationBundle\Migration\DeleteTranslationKeysQuery;

class OroSaleBundle implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    private $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->dropLockedColumn($schema, $queries);

        $this->addQuoteCustomerStatusField($schema);
        $this->addQuoteInternalStatusField($schema);
    }

    protected function addQuoteCustomerStatusField(Schema $schema)
    {
        $customerStatusOptions = new OroOptions();
        $customerStatusOptions->set('enum', 'immutable_codes', LoadQuoteCustomerStatuses::getDataKeys());

        $customerStatusEnumTable = $this->extendExtension->addEnumField(
            $schema,
            'oro_sale_quote',
            'customer_status',
            Quote::CUSTOMER_STATUS_CODE,
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );
        $customerStatusEnumTable->addOption(OroOptions::KEY, $customerStatusOptions);
    }

    protected function addQuoteInternalStatusField(Schema $schema)
    {
        $internalStatusOptions = new OroOptions();
        $internalStatusOptions->set('enum', 'immutable_codes', LoadQuoteInternalStatuses::getDataKeys());

        $internalStatusEnumTable = $this->extendExtension->addEnumField(
            $schema,
            'oro_sale_quote',
            'internal_status',
            Quote::INTERNAL_STATUS_CODE,
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );
        $internalStatusEnumTable->addOption(OroOptions::KEY, $internalStatusOptions);
    }

    protected function dropLockedColumn(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
        $table->dropColumn('locked');

        $queries->addPostQuery(new RemoveFieldQuery('Oro\Bundle\SaleBundle\Entity\Quote', 'locked'));

        $data = [
            'messages' => [
                'oro.sale.quote.locked.label',
                'oro.sale.quote.not_locked.label',
                'oro.sale.quote.notify_customer.by_email.notify_and_lock_warning',
                'oro.sale.btn.notify_and_lock'
            ]
        ];

        foreach ($data as $domain => $keys) {
            $queries->addQuery(new DeleteTranslationKeysQuery($domain, $keys));
        }
    }
}

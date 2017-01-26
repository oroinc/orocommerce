<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Migrations\Data\ORM\LoadQuoteCustomerStatuses;
use Oro\Bundle\SaleBundle\Migrations\Data\ORM\LoadQuoteInternalStatuses;

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
        $this->addQuoteCustomerStatusField($schema);
        $this->addQuoteInternalStatusField($schema);
    }

    /**
     * @param Schema $schema
     */
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

    /**
     * @param Schema $schema
     */
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
}

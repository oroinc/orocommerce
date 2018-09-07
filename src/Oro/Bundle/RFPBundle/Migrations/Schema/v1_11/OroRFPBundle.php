<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EmailBundle\Migrations\Schema\EditEmailTemplateQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RFPBundle\Mailer\Processor;

/**
 * ORO Migration which fixes localization of product units in request_create_confirmation email template.
 */
class OroRFPBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new EditEmailTemplateQuery(
            Processor::CONFIRM_REQUEST_TEMPLATE_NAME,
            '{{ item.unit }}',
            '{{ item.unit|oro_format_product_unit_label }}'
        ));
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;

class AddAssociation implements Migration, PaymentTermExtensionAwareInterface, OrderedMigrationInterface
{
    use PaymentTermExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_order');

        $queries->addPostQuery('UPDATE oro_order SET payment_term_7c4f1e8e_id = payment_term_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
}

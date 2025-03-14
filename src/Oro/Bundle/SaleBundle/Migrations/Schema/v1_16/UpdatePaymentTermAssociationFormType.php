<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSelectType;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;
use Oro\Bundle\SaleBundle\Entity\Quote;

class UpdatePaymentTermAssociationFormType implements Migration, PaymentTermExtensionAwareInterface
{
    use PaymentTermExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $associationName = $this->paymentTermExtension->getAssociationName($schema);

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                Quote::class,
                $associationName,
                'form',
                'form_type',
                PaymentTermSelectType::class,
                'oro_payment_term_select'
            )
        );
    }
}

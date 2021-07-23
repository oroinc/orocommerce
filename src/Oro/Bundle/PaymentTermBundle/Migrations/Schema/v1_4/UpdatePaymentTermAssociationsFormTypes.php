<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSelectType;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;

class UpdatePaymentTermAssociationsFormTypes implements Migration, PaymentTermExtensionAwareInterface
{
    use PaymentTermExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateFormTypeForEntity($schema, $queries, Customer::class);
        $this->updateFormTypeForEntity($schema, $queries, CustomerGroup::class);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function updateFormTypeForEntity(Schema $schema, QueryBag $queries, string $entity)
    {
        $associationName = $this->paymentTermExtension->getAssociationName($schema);

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                $entity,
                $associationName,
                'form',
                'form_type',
                PaymentTermSelectType::class,
                'oro_payment_term_select'
            )
        );
    }
}

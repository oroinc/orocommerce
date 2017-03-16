<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TranslationBundle\Migration\DeleteTranslationKeysQuery;

class OroAlternativeCheckoutBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->removeUnusedTranslationKeys($queries);
    }

    /**
     * @param QueryBag $queries
     */
    protected function removeUnusedTranslationKeys(QueryBag $queries)
    {
        $data = [
            'messages' => [
                'Alternative Checkout: Order Approval',
                'Alternative Checkout: Billing Information',
                'Alternative Checkout: Payment',
                'Alternative Checkout: Shipping Information',
                'Alternative Checkout: Shipping Method',
                'Alternative Checkout: Order Review',
                'Alternative Checkout: Request Approval',
                'Checkout: Billing Information',
                'Checkout: Payment',
                'Checkout: Shipping Information',
                'Checkout: Shipping Method',
                'Checkout: Order Review',
                'Alternative Checkout: Alternative Checkout: Order Approval',
                'Alternative Checkout: Alternative Checkout: Billing Information',
                'Alternative Checkout: Alternative Checkout: Payment',
                'Alternative Checkout: Alternative Checkout: Shipping Information',
                'Alternative Checkout: Alternative Checkout: Shipping Method',
                'Alternative Checkout: Alternative Checkout: Order Review',
                'Alternative Checkout: Alternative Checkout: Request Approval',
                'Checkout: Checkout: Billing Information',
                'Checkout: Checkout: Payment',
                'Checkout: Checkout: Shipping Information',
                'Checkout: Checkout: Shipping Method',
                'Checkout: Checkout: Order Review'
            ],
            'workflows' => [
                'Alternative Checkout: Order Approval',
                'Alternative Checkout: Billing Information',
                'Alternative Checkout: Payment',
                'Alternative Checkout: Shipping Information',
                'Alternative Checkout: Shipping Method',
                'Alternative Checkout: Order Review',
                'Alternative Checkout: Request Approval',
                'Checkout: Billing Information',
                'Checkout: Payment',
                'Checkout: Shipping Information',
                'Checkout: Shipping Method',
                'Checkout: Order Review'
            ]
        ];

        foreach ($data as $domain => $keys) {
            $queries->addQuery(new DeleteTranslationKeysQuery($domain, $keys));
        }
    }
}

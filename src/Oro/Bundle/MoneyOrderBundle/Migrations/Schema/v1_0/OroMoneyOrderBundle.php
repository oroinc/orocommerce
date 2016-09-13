<?php

namespace Oro\Bundle\FrontendBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ConfigBundle\Migration\RenameConfigSettingsQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;

class OroMoneyOrderBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameSystemConfigurationSettings(
            $queries,
            [
                Configuration::MONEY_ORDER_ENABLED_KEY,
                Configuration::MONEY_ORDER_LABEL_KEY,
                Configuration::MONEY_ORDER_SHORT_LABEL_KEY,
                Configuration::MONEY_ORDER_SORT_ORDER_KEY,
                Configuration::MONEY_ORDER_PAY_TO_KEY,
                Configuration::MONEY_ORDER_SEND_TO_KEY,
                Configuration::MONEY_ORDER_ALLOWED_COUNTRIES_KEY,
                Configuration::MONEY_ORDER_SELECTED_COUNTRIES_KEY,
                Configuration::MONEY_ORDER_ALLOWED_CURRENCIES,
            ]
        );
    }

    /**
     * @param QueryBag $queries
     * @param array $settings
     */
    private function renameSystemConfigurationSettings(QueryBag $queries, array $settings)
    {
        foreach ($settings as $name) {
            $queries->addPostQuery(new RenameConfigSettingsQuery("orob2b_money_order.$name", "oro_money_order.$name"));
        }
    }
}

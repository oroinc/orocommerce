<?php

namespace Oro\Bundle\FrontendBundle\Migrations\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ConfigBundle\Migration\RenameConfigSettingsQuery;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroMoneyOrderBundleInstaller implements Installation, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // update system configuration for installed instances
        if ($this->container->hasParameter('installed') && $this->container->getParameter('installed')) {
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

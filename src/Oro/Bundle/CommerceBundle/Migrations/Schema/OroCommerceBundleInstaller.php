<?php
declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CommerceBundle\Migrations\Schema\v4_1_0_0\RemoveInvoiceEntityConfig;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroCommerceBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v4_1_0_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            RemoveInvoiceEntityConfig::removeInvoiceEntityConfig($queries);
        }
    }
}

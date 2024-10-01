<?php

declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveTableQuery;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroCommerceBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v4_1_0_1';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $this->removeTables($queries);
        }
    }

    private function removeTables(QueryBag $queries): void
    {
        $classNames = [
            'Oro\Bundle\InvoiceBundle\Entity\Invoice',
            'Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem',
            'ACME\Bundle\WysiwygBundle\Entity\BlogPost',
            'ACME\Bundle\CollectOnDeliveryBundle\Entity\CollectOnDeliverySettings',
            'ACME\Bundle\FastShippingBundle\Entity\FastShippingSettings'
        ];
        foreach ($classNames as $className) {
            if (!class_exists($className, false)) {
                $queries->addQuery(new RemoveTableQuery($className));
            }
        }
    }
}

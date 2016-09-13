<?php

namespace Oro\Bundle\FrontendBundle\Migrations\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ConfigBundle\Migration\RenameConfigSettingsQuery;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFrontendBundleInstaller implements Installation, ContainerAwareInterface
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
            $queries->addPostQuery(
                new RenameConfigSettingsQuery(
                    'oro_b2b_frontend.frontend_theme',
                    'oro_frontend.frontend_theme'
                )
            );
        }
    }
}

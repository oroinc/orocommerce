<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Isolation;

use Doctrine\DBAL\Connection;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Removes Combined Price Lists if flat Pricing Storage enabled.
 */
class PricingStorageIsolator implements IsolatorInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContainerInterface $container)
    {
        if (!$container->getParameter('installed')) {
            return false;
        }

        $storage = $container->get('oro_config.global')->get('oro_pricing.price_storage');

        return $storage === 'flat' || $storage === 'combined';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'PricingStorage';
    }

    /**
     * {@inheritdoc}
     */
    public function start(BeforeStartTestsEvent $event)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $container = $this->kernel->getContainer();
        $storage = $container->get('oro_config.global')->get('oro_pricing.price_storage');

        if ($storage === 'flat') {
            if (!$container->has('oro_pricing.behat.pricing_storage_switch_handler')) {
                $event->writeln(
                    '<error>
                        Service `oro_pricing.behat.pricing_storage_switch_handler` not found.
                        Pricing storage related tests may behave incorrectly as associations will be not moved.
                        Please check that all Tests/Behat/parameters.yml files were merged into parameters.yml
                        and caches were warmed for prod environment.
                    </error>'
                );
            } else {
                $container->get('oro_pricing.behat.pricing_storage_switch_handler')
                    ->moveAssociationsForFlatPricingStorage();
            }

            $event->writeln('<info>Removing Combined Price Lists as flat Pricing Storage enabled</info>');
            /** @var Connection $connection */
            $connection = $container->get('doctrine')->getConnection();
            $connection->executeQuery('DELETE FROM oro_price_list_combined');
        }
        if ($storage === 'combined') {
            $event->writeln('<info>Rebuilding Combined Price Lists</info>');
            $container->get('oro_pricing.builder.combined_price_list_builder_facade')->rebuildAll(time());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function restoreState(RestoreStateEvent $event)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'pricing_storage';
    }
}

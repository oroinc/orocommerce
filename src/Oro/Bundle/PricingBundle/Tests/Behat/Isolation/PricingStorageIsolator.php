<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Isolation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListAssociationsProvider;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

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

    #[\Override]
    public function isApplicable(ContainerInterface $container)
    {
        if (!$container->get(ApplicationState::class)->isInstalled()) {
            return false;
        }

        $storage = $container->get('oro_config.global')->get('oro_pricing.price_storage');

        return $storage === 'flat' || $storage === 'combined';
    }

    #[\Override]
    public function getName()
    {
        return 'PricingStorage';
    }

    #[\Override]
    public function start(BeforeStartTestsEvent $event)
    {
    }

    #[\Override]
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $container = $this->kernel->getContainer();
        $storage = $container->get('oro_config.global')->get('oro_pricing.price_storage');

        if ($storage === 'flat') {
            if (!$container->has('oro_pricing.pricing_storage_switch_handler')) {
                $event->writeln(
                    '<error>
                        Service `oro_pricing.pricing_storage_switch_handler` not found.
                        Pricing storage related tests may behave incorrectly as associations will be not moved.
                        Please check that all Tests/Behat/parameters.yml files were merged into parameters.yml
                        and caches were warmed for prod environment.
                    </error>'
                );
            } else {
                $container->get('oro_pricing.pricing_storage_switch_handler')
                    ->moveAssociationsForFlatPricingStorage();
            }

            $event->writeln('<info>Removing Combined Price Lists as flat Pricing Storage enabled</info>');
            /** @var Connection $connection */
            $connection = $container->get('doctrine')->getConnection();
            $connection->executeQuery('DELETE FROM oro_price_list_combined');
        }
        if ($storage === 'combined') {
            $event->writeln('<info>Rebuilding Combined Price Lists</info>');
            if ($this->isDbalTransport($container)) {
                // The DBAL queue is restored with the database dump, so the consumers can process the restored
                // CPL messages at the same time and insert the same config values first.
                // Thus the CPLs are rebuilt in the maintenance mode.
                $this->runCommand('oro:maintenance:lock');
                try {
                    $this->rebuildCombinedPriceLists($container);
                } finally {
                    $this->runCommand('oro:maintenance:unlock');
                }
            } else {
                // The RabbitMQ queue is purged before the test, and the maintenance mode restarts the consumers,
                // which delays the indexation of the prices for the first scenarios.
                // Thus the CPLs are rebuilt without the maintenance mode, and the rebuild is repeated
                // when a consumer processes a CPL message of the fixtures at the same time.
                $this->rebuildCombinedPriceListsWithRetry($container, $event);
            }
        }
    }

    private function isDbalTransport(ContainerInterface $container): bool
    {
        return str_starts_with((string)$container->getParameter('message_queue_transport_dsn'), 'dbal:');
    }

    private function rebuildCombinedPriceListsWithRetry(
        ContainerInterface $container,
        BeforeIsolatedTestEvent $event
    ): void {
        try {
            $this->rebuildCombinedPriceLists($container);
        } catch (UniqueConstraintViolationException $e) {
            $event->writeln('<comment>Rebuilding Combined Price Lists again: ' . $e->getMessage() . '</comment>');
            $doctrine = $container->get('doctrine');
            foreach (array_keys($doctrine->getManagerNames()) as $name) {
                if (!$doctrine->getManager($name)->isOpen()) {
                    $doctrine->resetManager($name);
                }
            }
            $container->get('oro_config.global')->flush();
            $this->rebuildCombinedPriceLists($container);
        }
    }

    private function rebuildCombinedPriceLists(ContainerInterface $container): void
    {
        /** @var CombinedPriceListAssociationsProvider $associationsProvider */
        $associationsProvider = $container->get('oro_pricing.combined_price_list_associations_provider');
        /** @var CombinedPriceListProvider $cplProvider */
        $cplProvider = $container->get('oro_pricing.provider.combined_price_list');
        /** @var CombinedPriceListsBuilderFacade $cplBuilderFacade */
        $cplBuilderFacade = $container->get('oro_pricing.builder.combined_price_list_builder_facade');

        $associations = $associationsProvider->getCombinedPriceListsWithAssociations(true);
        foreach ($associations as $association) {
            $cpl = $cplProvider->getCombinedPriceListByCollectionInformation($association['collection']);
            $cplBuilderFacade->rebuild([$cpl]);
            $assignTo = $association['assign_to'] ?? [];
            if (!empty($assignTo)) {
                $cplBuilderFacade->processAssignments($cpl, $association['assign_to'], null, true);
            }
            $cplBuilderFacade->triggerProductIndexation($cpl, $assignTo);
        }
    }

    /**
     * Runs the command in a separate process the same way as
     * {@see \Oro\Bundle\TestFrameworkBundle\Behat\Isolation\MaintenanceModeIsolator::runCommand()}.
     */
    private function runCommand(string $name): void
    {
        $process = new Process([
            (new PhpExecutableFinder())->find(),
            realpath($this->kernel->getProjectDir()) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console',
            $name,
            '-n',
            sprintf('--env=%s', $this->kernel->getEnvironment()),
        ]);
        $process->mustRun();
        // Renew the cache state to force restart the consumer, otherwise it may notice the maintenance mode too late.
        $this->kernel->getContainer()->get('oro_message_queue.consumption.cache_state')->renewChangeDate();
        sleep(1);
    }

    #[\Override]
    public function afterTest(AfterIsolatedTestEvent $event)
    {
    }

    #[\Override]
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    #[\Override]
    public function restoreState(RestoreStateEvent $event)
    {
    }

    #[\Override]
    public function isOutdatedState()
    {
        return false;
    }

    #[\Override]
    public function getTag()
    {
        return 'pricing_storage';
    }
}

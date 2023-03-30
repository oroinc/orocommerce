<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Isolation;

use Doctrine\DBAL\Connection;
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
        if (!$container->get(ApplicationState::class)->isInstalled()) {
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

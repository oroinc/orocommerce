<?php
declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prepares and activates combined price lists based on their schedules.
 */
class CombinedPriceListScheduleCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:price-lists:schedule';

    private ManagerRegistry $doctrine;
    private ConfigManager $configManager;
    private CombinedPriceListScheduleResolver $priceListScheduleResolver;
    private CombinedPriceListTriggerHandler $triggerHandler;
    private CombinedPriceListsBuilderFacade $builder;

    public function __construct(
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        CombinedPriceListScheduleResolver $priceListScheduleResolver,
        CombinedPriceListTriggerHandler $triggerHandler,
        CombinedPriceListsBuilderFacade $builder
    ) {
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->priceListScheduleResolver = $priceListScheduleResolver;
        $this->triggerHandler = $triggerHandler;
        $this->builder = $builder;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Prepares and activates combined price lists based on their schedules.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command prepares and activates combined price lists
based on their schedules.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        $offsetHours = $this->configManager->get('oro_pricing.offset_of_processing_cpl_prices');

        $count = $this->doctrine->getRepository(CombinedPriceList::class)
            ->getCPLsForPriceCollectByTimeOffsetCount($offsetHours);

        return ($count > 0);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->triggerHandler->startCollect();

        // Build not calculated CPLs before switch
        $this->combinePricesForScheduledCPL();
        // Switch to scheduled CPLs according to activation schedule
        $this->priceListScheduleResolver->updateRelations();

        $this->triggerHandler->commit();

        return self::SUCCESS;
    }

    protected function combinePricesForScheduledCPL()
    {
        $offsetHours = $this->configManager->get('oro_pricing.offset_of_processing_cpl_prices');

        $combinedPriceLists = $this->doctrine->getRepository(CombinedPriceList::class)
            ->getCPLsForPriceCollectByTimeOffset($offsetHours);

        $buildActivityRepo = $this->doctrine->getRepository(CombinedPriceListBuildActivity::class);
        $buildActivityRepo->addBuildActivities($combinedPriceLists);

        $this->builder->rebuild($combinedPriceLists);
        foreach ($combinedPriceLists as $combinedPriceList) {
            $buildActivityRepo->deleteActivityRecordsForCombinedPriceList($combinedPriceList);
            $this->builder->triggerProductIndexation($combinedPriceList);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '*/5 * * * *';
    }
}

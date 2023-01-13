<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionsScheduleConfigurationListener;
use Oro\Bundle\ProductBundle\Exception\FailedToRunReindexProductCollectionJobException;
use Oro\Bundle\ProductBundle\Handler\AsyncReindexProductCollectionHandlerInterface;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\ProductBundle\Provider\CronSegmentsProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Schedules indexation of product collections.
 */
class ProductCollectionsIndexCronCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:product-collections:index';

    private AsyncReindexProductCollectionHandlerInterface $collectionIndexationHandler;
    private SegmentMessageFactory $messageFactory;
    private CronSegmentsProvider $segmentProvider;
    private ProductCollectionSegmentHelper $productCollectionHelper;
    private ConfigManager $configManager;

    public function __construct(
        AsyncReindexProductCollectionHandlerInterface $collectionIndexationHandler,
        SegmentMessageFactory $segmentMessageFactory,
        CronSegmentsProvider $cronSegmentsProvider,
        ProductCollectionSegmentHelper $productCollectionSegmentHelper,
        ConfigManager $configManager
    ) {
        $this->collectionIndexationHandler = $collectionIndexationHandler;
        $this->messageFactory = $segmentMessageFactory;
        $this->segmentProvider = $cronSegmentsProvider;
        $this->productCollectionHelper = $productCollectionSegmentHelper;
        $this->configManager = $configManager;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->addOption('partial-reindex', null, null, 'Perform indexation only for added or removed products')
            ->setDescription('Schedules indexation of product collections.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command schedules indexation of product collections.

Such indexation is necessary for the product collections that have filters with dependencies
on other entities.

This command only schedules the job by adding a message to the message queue, so ensure
that the message consumer processes (<info>oro:message-queue:consume</info>) are running.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hasSchedules = false;
        if ($input->getOption('partial-reindex')) {
            $isFull = false;
        } else {
            $isFull = !(bool)$this->configManager->get('oro_product.product_collections_indexation_partial');
        }

        $rootJobName = sprintf(
            '%s:%s:%s',
            ReindexProductCollectionBySegmentTopic::NAME,
            'cron',
            $isFull ? 'full' : 'partial'
        );
        $partialMessageIterator = $this->getPartialMessageIterator($isFull, $hasSchedules, $output);

        try {
            $this->collectionIndexationHandler->handle(
                $partialMessageIterator,
                $rootJobName,
                true,
                ['main', 'collection_sort_order']
            );
        } catch (FailedToRunReindexProductCollectionJobException $jobException) {
            $output->writeln(
                sprintf(
                    '<error>Can\'t start the process because the same job on %s re-indexation is in progress.</error>',
                    $isFull ? 'full' : 'partial'
                )
            );

            return self::FAILURE;
        }

        if ($hasSchedules) {
            $output->writeln('<info>Product collections indexation has been successfully scheduled</info>');
        } else {
            $output->writeln('<info>There are no suitable segments for indexation</info>');
        }

        return self::SUCCESS;
    }

    private function getPartialMessageIterator(bool $isFull, bool &$hasSchedules, OutputInterface $output): \Generator
    {
        foreach ($this->segmentProvider->getSegments() as $segment) {
            $websiteIds = $this->productCollectionHelper->getWebsiteIdsBySegment($segment);
            if (empty($websiteIds)) {
                continue;
            }
            $output->writeln(
                sprintf(
                    '<info>Scheduling %s indexation of segment id %d for websites: %s</info>',
                    $isFull ? 'full' : 'partial',
                    $segment->getId(),
                    implode(', ', $websiteIds)
                )
            );

            $hasSchedules = true;
            yield $this->messageFactory->getPartialMessageData(
                $websiteIds,
                $segment,
                null,
                $isFull
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return $this->configManager->get(ProductCollectionsScheduleConfigurationListener::CONFIG_FIELD);
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionsScheduleConfigurationListener;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\ProductBundle\Provider\CronSegmentsProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command class schedules cron based product collection indexation.
 */
class ProductCollectionsIndexCronCommand extends Command implements CronCommandInterface
{
    private const OPTION_PARTIAL_REINDEX = 'partial-reindex';

    /** @var string */
    protected static $defaultName = 'oro:cron:product-collections:index';

    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var SegmentMessageFactory */
    private $messageFactory;

    /** @var CronSegmentsProvider */
    private $segmentProvider;

    /** @var ProductCollectionSegmentHelper */
    private $productCollectionHelper;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param MessageProducerInterface $messageProducer
     * @param SegmentMessageFactory $segmentMessageFactory
     * @param CronSegmentsProvider $cronSegmentsProvider
     * @param ProductCollectionSegmentHelper $productCollectionSegmentHelper
     * @param ConfigManager $configManager
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        SegmentMessageFactory $segmentMessageFactory,
        CronSegmentsProvider $cronSegmentsProvider,
        ProductCollectionSegmentHelper $productCollectionSegmentHelper,
        ConfigManager $configManager
    ) {
        $this->messageProducer = $messageProducer;
        $this->messageFactory = $segmentMessageFactory;
        $this->segmentProvider = $cronSegmentsProvider;
        $this->productCollectionHelper = $productCollectionSegmentHelper;
        $this->configManager = $configManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $description = <<<DESC
Add message to queue to index product collections for which filter contains dependencies on other entities.
DESC;

        $this
            ->addOption(
                self::OPTION_PARTIAL_REINDEX,
                null,
                null,
                'Perform collection partial indexation for added/removed products only'
            )
            ->setDescription($description);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hasSchedules = false;
        if ($input->getOption(self::OPTION_PARTIAL_REINDEX)) {
            $isFull = false;
        } else {
            $isFull = !(bool)$this->configManager->get('oro_product.product_collections_indexation_partial');
        }

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
            $this->messageProducer->send(
                Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                $this->messageFactory->createMessage($websiteIds, $segment, null, $isFull)
            );
        }

        if ($hasSchedules) {
            $output->writeln('<info>Product collections indexation has been successfully scheduled</info>');
        } else {
            $output->writeln('<info>There are no suitable segments for indexation</info>');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return $this->configManager->get(ProductCollectionsScheduleConfigurationListener::CONFIG_FIELD);
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return true;
    }
}

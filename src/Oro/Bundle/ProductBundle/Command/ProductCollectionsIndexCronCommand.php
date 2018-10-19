<?php

namespace Oro\Bundle\ProductBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionsScheduleConfigurationListener;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\ProductBundle\Provider\CronSegmentsProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command class schedules cron based product collection indexation.
 */
class ProductCollectionsIndexCronCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:product-collections:index';

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var SegmentMessageFactory
     */
    private $messageFactory;

    /**
     * @var CronSegmentsProvider
     */
    private $segmentProvider;

    /**
     * @var ProductCollectionSegmentHelper
     */
    private $productCollectionHelper;

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function setMessageProducer(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param SegmentMessageFactory $messageFactory
     */
    public function setMessageFactory(SegmentMessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param CronSegmentsProvider $segmentProvider
     */
    public function setSegmentProvider(CronSegmentsProvider $segmentProvider)
    {
        $this->segmentProvider = $segmentProvider;
    }

    /**
     * @param ProductCollectionSegmentHelper $productCollectionHelper
     */
    public function setProductCollectionHelper(ProductCollectionSegmentHelper $productCollectionHelper)
    {
        $this->productCollectionHelper = $productCollectionHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $description = <<<DESC
Add message to queue to index product collections for which filter contains dependencies on other entities.
DESC;

        $this->setName(self::NAME)
            ->setDescription($description);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hasSchedules = false;
        foreach ($this->segmentProvider->getSegments() as $segment) {
            $websiteIds = $this->productCollectionHelper->getWebsiteIdsBySegment($segment);
            if (empty($websiteIds)) {
                continue;
            }
            $output->writeln(
                sprintf(
                    '<info>Scheduling indexation of segment id %d for websites: %s</info>',
                    $segment->getId(),
                    implode(', ', $websiteIds)
                )
            );

            $hasSchedules = true;
            $this->messageProducer->send(
                Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                $this->messageFactory->createMessage($websiteIds, $segment)
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
        $configManager = $this->getContainer()->get('oro_config.manager');
        return $configManager->get(ProductCollectionsScheduleConfigurationListener::CONFIG_FIELD);
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return true;
    }
}

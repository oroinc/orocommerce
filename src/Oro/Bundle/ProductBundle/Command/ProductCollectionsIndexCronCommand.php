<?php

namespace Oro\Bundle\ProductBundle\Command;

use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\ProductBundle\Provider\SegmentWithRelationsProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command class schedules cron based product collection indexation.
 */
class ProductCollectionsIndexCronCommand extends ContainerAwareCommand
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
     * @var SegmentWithRelationsProvider
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
     * @param SegmentWithRelationsProvider $segmentProvider
     */
    public function setSegmentProvider(SegmentWithRelationsProvider $segmentProvider)
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
        foreach ($this->segmentProvider->getSegmentsWithRelations() as $segment) {
            $websiteIds = $this->productCollectionHelper->getWebsiteIdsBySegment($segment);
            if (empty($websiteIds)) {
                continue;
            }
            $output->writeln(
                sprintf(
                    '<info>Scheduling indexation for segment id %d, websites: %s</info>',
                    $segment->getId(),
                    implode(', ', $websiteIds)
                )
            );

            $hasSchedules = true;
            $this->messageProducer->send(
                Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                $this->messageFactory->createMessage($segment, $websiteIds)
            );
        }

        if ($hasSchedules) {
            $output->writeln('<info>Product collections indexation has been successfully scheduled</info>');
        } else {
            $output->writeln('<info>There are no suitable segments for indexation</info>');
        }
    }
}

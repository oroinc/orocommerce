<?php

namespace Oro\Bundle\WebsiteSearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ReindexCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:website-search:reindex';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                'Full or compact class name of entity which should be reindexed' .
                '(e.g. Oro\Bundle\UserBundle\Entity\User or OroUserBundle:User)'
            )
            ->addOption(
                'website-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'INTEGER. Website ID needs to reindex'
            )
            ->addOption(
                'scheduled',
                null,
                InputOption::VALUE_NONE,
                'Enforces a scheduled (background) reindexation'
            )
            ->addOption(
                'product-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Product IDs to index. Expression e.g. range "1-50" or "*/100". Requires scheduled mode',
                []
            )
            ->setDescription('Rebuild search index for certain website/entity type or all mapped entities');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getOption('class');
        $websiteId = $input->getOption('website-id');
        $isScheduled = $input->getOption('scheduled');
        $productId = $input->getOption('product-id');

        $class = $class ? $this->getFQCN($class) : null;
        
        $classes = $class ? [$class] : [];
        $websiteIds = $websiteId ? [(int)$websiteId] : [];

        $output->writeln($this->getStartingMessage($class, $websiteId));

        $productId = $this->parseProductIdOption($output, $productId, $isScheduled);

        $this->fireReindexationEvents($classes, $websiteIds, $productId, $isScheduled);

        $output->writeln('Reindex finished successfully.');
    }

    /**
     * @param string|null $class
     * @param int $websiteId
     * @return string
     */
    private function getStartingMessage($class, $websiteId)
    {
        $websitePlaceholder = $websiteId ? sprintf(' and website ID %d', $websiteId) : '';

        return sprintf(
            'Starting reindex task for %s%s...',
            $class ?: 'all mapped entities',
            $websitePlaceholder
        );
    }

    /**
     * @param string $class
     * @return string
     */
    private function getFQCN($class)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass($class)
            ->getClassMetadata($class)
            ->getName();
    }

    /**
     * @return mixed
     */
    private function getLastProductId()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->getProductsQueryBuilder()
            ->select('MAX(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string $productId
     * @return bool
     */
    private function getChunkSizeFromProductIdOption($productId)
    {
        // anything that ends with "/{number}"
        if (!preg_match('/\/([\d]+)$/', $productId, $matches)) {
            return false;
        }

        return $matches[1];
    }

    /**
     * @param string $productId
     * @return array|bool
     */
    private function getRangeFromProductIdOption($productId)
    {
        // anything that begins with "{number}-{number}"
        if (!preg_match('/^([\d]+)\-([\d]+)/', $productId, $matches)) {
            return false;
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * @param int $chunkSize
     * @param array $range
     * @return string
     */
    private function createProductIdMessage($chunkSize, $range)
    {
        $message = 'Generating indexation requests';

        if (false !== $chunkSize) {
            $message .= ' ' . $chunkSize . ' products each';
        }
        if (false !== $range) {
            $message .= ' for an ID range of ' . $range[0] . '-' . $range[1];
        }
        $message .= '...';

        return $message;
    }

    /**
     * @param OutputInterface $output
     * @param string|array    $productId
     * @param bool            $isScheduled
     * @return array
     */
    private function parseProductIdOption($output, $productId, $isScheduled)
    {
        if (empty($productId) || !is_string($productId)) {
            return $productId;
        }

        $chunkSize = $this->getChunkSizeFromProductIdOption($productId);
        $range     = $this->getRangeFromProductIdOption($productId);

        if (false === $chunkSize && false === $range) {
            throw new \RuntimeException('Cannot understand value: ' . $productId);
        }

        if (false === $isScheduled && false !== $chunkSize) {
            throw new \RuntimeException('Splitting products makes only sense with --scheduled');
        }

        $message = $this->createProductIdMessage($chunkSize, $range);
        $output->writeln($message);

        if (false !== $range) {
            $result  = range($range[0], $range[1]);
        } else {
            $result = $this->getLastProductId();
            $result = range(1, $result);
        }

        if (false !== $chunkSize) {
            $result = array_chunk($result, $chunkSize);
        } else {
            $result = [$result];
        }

        return $result;
    }

    /**
     * @param array $classes
     * @param array $websiteIds
     * @param array $productId
     * @param bool $isScheduled
     */
    private function fireReindexationEvents(
        $classes,
        $websiteIds,
        $productId,
        $isScheduled
    ) {
        if (!is_array($productId) || empty($productId)) {
            $event = new ReindexationRequestEvent($classes, $websiteIds, $productId, $isScheduled);
            $dispatcher = $this->getContainer()->get('event_dispatcher');
            $dispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
            return;
        }

        foreach ($productId as $chunk) {
            $event = new ReindexationRequestEvent([Product::class], $websiteIds, $chunk, $isScheduled);
            $dispatcher = $this->getContainer()->get('event_dispatcher');
            $dispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
        }
    }
}

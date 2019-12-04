<?php

namespace Oro\Bundle\WebsiteSearchBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Reindex command for front store search
 */
class ReindexCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:website-search:reindex';

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param ManagerRegistry $doctrine
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ManagerRegistry $doctrine, EventDispatcherInterface $eventDispatcher)
    {
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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
                'ids',
                null,
                InputOption::VALUE_OPTIONAL,
                'Entity IDs to index. Expression e.g. range "1-50" or "*/100". '
                .'Available only with \'class\' parameter. Requires scheduled mode',
                ''
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
        $entityId = $input->getOption('ids');

        $class = $class ? $this->getFQCN($class) : null;
        
        $classes = $class ? [$class] : [];
        $websiteIds = $websiteId ? [(int)$websiteId] : [];

        $output->writeln($this->getStartingMessage($class, $websiteId));

        $entityIds = $this->parseEntityIdOption($output, $class, $entityId, $isScheduled);

        if (!is_array($element = reset($entityIds))) {
            $this->fireReindexationEvents($classes, $websiteIds, $entityIds, $isScheduled);
        } else {
            $this->fireReindexationEventsForChunks($classes, $websiteIds, $entityIds, $isScheduled);
        }

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
        return $this->doctrine
            ->getManagerForClass($class)
            ->getClassMetadata($class)
            ->getName();
    }

    /**
     * @param string $className
     * @return mixed
     */
    private function getLastEntityId($className)
    {
        return $this->doctrine
            ->getManagerForClass($className)
            ->getRepository($className)
            ->createQueryBuilder('a')
            ->select('MAX(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string $entityId
     * @return bool
     */
    private function getChunkSizeFromEntityIdOption($entityId)
    {
        // anything that ends with "/{number}"
        if (!preg_match('/\/([\d]+)$/', $entityId, $matches)) {
            return false;
        }

        return $matches[1];
    }

    /**
     * @param string $entityId
     * @return array|bool
     */
    private function getRangeFromEntityIdOption($entityId)
    {
        // anything that begins with "{number}-{number}"
        if (!preg_match('/^([\d]+)\-([\d]+)/', $entityId, $matches)) {
            return false;
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * @param int $chunkSize
     * @param array $range
     * @return string
     */
    private function createEntityIdMessage($chunkSize, $range)
    {
        $message = 'Generating indexation requests';

        if (false !== $chunkSize) {
            $message .= ' ' . $chunkSize . ' entities each';
        }
        if (false !== $range) {
            $message .= ' for an ID range of ' . $range[0] . '-' . $range[1];
        }
        $message .= '...';

        return $message;
    }

    /**
     * @param OutputInterface $output
     * @param string $className
     * @param bool $isScheduled
     * @param string $entityId
     * @return array
     */
    private function parseEntityIdOption($output, $className, $entityId, $isScheduled)
    {
        if (empty($entityId)) {
            return [];
        }

        if (!$className) {
            throw new \InvalidArgumentException('--class option is required when using --ids');
        }

        $chunkSize = $this->getChunkSizeFromEntityIdOption($entityId);
        $range     = $this->getRangeFromEntityIdOption($entityId);

        if (false === $chunkSize && false === $range) {
            throw new \RuntimeException('Cannot understand value: ' . $entityId);
        }

        if (false === $isScheduled && false !== $chunkSize) {
            throw new \RuntimeException('Splitting entities makes only sense with --scheduled');
        }

        $message = $this->createEntityIdMessage($chunkSize, $range);
        $output->writeln($message);

        if (false !== $range) {
            $result  = range($range[0], $range[1]);
        } else {
            $result = $this->getLastEntityId($className);
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
     * @param array $entityId
     * @param bool $isScheduled
     */
    private function fireReindexationEvents($classes, $websiteIds, array $entityId, $isScheduled)
    {
        $event = new ReindexationRequestEvent($classes, $websiteIds, $entityId, $isScheduled);
        $this->eventDispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
    }

    /**
     * @param array $classes
     * @param array $websiteIds
     * @param array $chunks
     * @param bool $isScheduled
     */
    private function fireReindexationEventsForChunks($classes, $websiteIds, array $chunks, $isScheduled)
    {
        foreach ($chunks as $chunk) {
            $this->fireReindexationEvents($classes, $websiteIds, $chunk, $isScheduled);
        }
    }
}

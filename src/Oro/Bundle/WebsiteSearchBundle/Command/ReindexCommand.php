<?php
declare(strict_types=1);

namespace Oro\Bundle\WebsiteSearchBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Rebuilds the storefront search index.
 */
class ReindexCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:website-search:reindex';

    private ManagerRegistry $doctrine;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(ManagerRegistry $doctrine, EventDispatcherInterface $eventDispatcher)
    {
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('class', null, InputOption::VALUE_OPTIONAL, 'Entity to reindex (FQCN or short name)')
            ->addOption('website-id', null, InputOption::VALUE_OPTIONAL, 'ID (integer) of the website to reindex')
            ->addOption('scheduled', null, InputOption::VALUE_NONE, 'Schedule the reindexation in the background')
            ->addOption('ids', null, InputOption::VALUE_OPTIONAL, 'IDs of the entities to reindex', '')
            ->setDescription('Rebuilds the storefront search index.')
            ->setHelp(
            // @codingStandardsIgnoreStart
            <<<'HELP'
The <info>%command.name%</info> command rebuilds the storefront search index.

The scope of the reindexation can be limited to search indexes of a specific website
with the <info>--website-id</info> option:

  <info>php %command.full_name% --website-id=<ID></info>

You can limit the reindexation to a specific entity with the <info>--class</info> option.
Both the FQCN (Oro\Bundle\UserBundle\Entity\User) and short (OroUserBundle:User)
class names are accepted:

  <info>php %command.full_name% --class=<entity></info>

The reindexation can be further limited to specific entities by providing entity IDs
with the <info>--ids</info> option (accepts expressions, e.g. range "1-50" or "*/100").
It works in conjunction with <info>--class</info> option and only in the scheduled background
execution mode (<info>--scheduled</info>):

  <info>php %command.full_name% --scheduled --class=<entity> --ids=<expression></info>

When using the <info>--scheduled</info> option this command only schedules the job
by adding a message to the message queue, so ensure that the message consumer processes
(<info>oro:message-queue:consume</info>) are running for the actual reindexation to happen.

HELP
            // @codingStandardsIgnoreEnd
            )
            ->addUsage('--scheduled')
            ->addUsage('--website-id=<ID>')
            ->addUsage('--scheduled --website-id=<ID>')
            ->addUsage('--class=<entity>')
            ->addUsage('--scheduled --class=<entity>')
            ->addUsage('--scheduled --class=<entity> --ids=<expression>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

        if (!is_array(reset($entityIds))) {
            $this->fireReindexationEvents($classes, $websiteIds, $entityIds, $isScheduled);
        } else {
            $this->fireReindexationEventsForChunks($classes, $websiteIds, $entityIds, $isScheduled);
        }

        $output->writeln('Reindex finished successfully.');
    }

    private function getStartingMessage(?string $class, $websiteId): string
    {
        $websitePlaceholder = $websiteId ? sprintf(' and website ID %d', $websiteId) : '';

        return sprintf(
            'Starting reindex task for %s%s...',
            $class ?: 'all mapped entities',
            $websitePlaceholder
        );
    }

    private function getFQCN(string $class): string
    {
        return $this->doctrine->getManagerForClass($class)->getClassMetadata($class)->getName();
    }

    /**
     * @return mixed
     */
    private function getLastEntityId(string $className)
    {
        return $this->doctrine
            ->getManagerForClass($className)
            ->getRepository($className)
            ->createQueryBuilder('a')
            ->select('MAX(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getChunkSizeFromEntityIdOption(string $entityId): ?int
    {
        // anything that ends with "/{number}"
        if (!preg_match('/\/([\d]+)$/', $entityId, $matches)) {
            return null;
        }

        return \intval($matches[1]);
    }

    /** @return null|array<int, int> */
    private function getRangeFromEntityIdOption(string $entityId): ?array
    {
        // anything that begins with "{number}-{number}"
        if (!preg_match('/^([\d]+)\-([\d]+)/', $entityId, $matches)) {
            return null;
        }

        return [\intval($matches[1]), \intval($matches[2])];
    }

    private function createEntityIdMessage(?int $chunkSize, ?array $range): string
    {
        $message = 'Generating indexation requests';

        if (null !== $chunkSize) {
            $message .= ' ' . $chunkSize . ' entities each';
        }
        if (null !== $range) {
            $message .= ' for an ID range of ' . $range[0] . '-' . $range[1];
        }
        $message .= '...';

        return $message;
    }

    private function parseEntityIdOption(
        OutputInterface $output,
        ?string $className,
        ?string $entityId,
        bool $isScheduled
    ): array {
        if (empty($entityId)) {
            return [];
        }

        if (!$className) {
            throw new \InvalidArgumentException('--class option is required when using --ids');
        }

        $chunkSize = $this->getChunkSizeFromEntityIdOption($entityId);
        $range     = $this->getRangeFromEntityIdOption($entityId);

        if (null === $chunkSize && null === $range) {
            throw new \RuntimeException('Cannot understand value: ' . $entityId);
        }

        if (false === $isScheduled && null !== $chunkSize) {
            throw new \RuntimeException('Splitting entities makes only sense with --scheduled');
        }

        $message = $this->createEntityIdMessage($chunkSize, $range);
        $output->writeln($message);

        if (null !== $range) {
            $result  = range($range[0], $range[1]);
        } else {
            $result = $this->getLastEntityId($className);
            $result = range(1, $result);
        }

        if (null !== $chunkSize) {
            $result = array_chunk($result, $chunkSize);
        } else {
            $result = [$result];
        }

        return $result;
    }

    private function fireReindexationEvents(array $classes, array $websiteIds, array $entityId, bool $isScheduled): void
    {
        $event = new ReindexationRequestEvent($classes, $websiteIds, $entityId, $isScheduled);
        $this->eventDispatcher->dispatch($event, ReindexationRequestEvent::EVENT_NAME);
    }

    private function fireReindexationEventsForChunks(
        array $classes,
        array $websiteIds,
        array $chunks,
        bool $isScheduled
    ): void {
        foreach ($chunks as $chunk) {
            $this->fireReindexationEvents($classes, $websiteIds, $chunk, $isScheduled);
        }
    }
}

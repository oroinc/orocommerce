<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Command;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Schedules the (re)build of resized versions of all product images.
 */
class ResizeAllProductImagesCommand extends Command
{
    private const BATCH_SIZE = 1000;

    protected array $noImagesPath = [];

    /** @var string */
    protected static $defaultName = 'product:image:resize-all';

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private EventDispatcherInterface $eventDispatcher,
        private CacheManager $cacheManager
    ) {
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->addOption('force', null, null, 'Overwrite existing images')
            ->addOption(
                'dimension',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Resize to given dimension(s)',
                []
            )
            ->setDescription('Schedules the (re)build of resized versions of all product images.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command schedules the (re)build of resized versions of all product images.

This command only schedules the job by adding a message to the message queue, so ensure
that the message consumer processes (<info>oro:message-queue:consume</info>) are running
to get the images actually resized.

  <info>php %command.full_name%</info>

The <info>--force</info> option can be used to overwrite existing images:

  <info>php %command.full_name% --force</info>
  
The list of target dimensions can be provided using the <info>--dimension</info> option:

  <info>php %command.full_name% --dimension=<dimension1> --dimension=<dimension2> --dimension=<dimensionN></info>

HELP
            )
            ->addUsage('--force')
            ->addUsage('--dimension=<dimension1> --dimension=<dimension2> --dimension=<dimensionN>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $iterator = $this->getProductImagesIterator();
        $entitiesProcessed = 0;
        $forceOption = $this->getForceOption($input);

        foreach ($iterator as $productImage) {
            $this->getEventDispatcher()->dispatch(
                new ProductImageResizeEvent($productImage['id'], $forceOption, $this->getDimensionsOption($input)),
                ProductImageResizeEvent::NAME
            );
            $entitiesProcessed++;
        }

        $this->removeNoImagesCache($output);

        $output->writeln(sprintf('%d product image(s) queued for resize.', $entitiesProcessed));

        return Command::SUCCESS;
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    protected function getProductImagesIterator(): BufferedIdentityQueryResultIterator
    {
        $queryBuilder = $this->doctrineHelper
            ->getEntityRepositoryForClass(ProductImage::class)
            ->createQueryBuilder('productImage')
            ->select('productImage.id');

        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);

        return $iterator;
    }

    protected function getForceOption(InputInterface $input): bool
    {
        return (bool)$input->getOption('force');
    }

    /**
     * @return string[]|null
     */
    protected function getDimensionsOption(InputInterface $input): ?array
    {
        return $input->getOption('dimension');
    }

    public function addNoImagePath($path): void
    {
        $this->noImagesPath[] = $path;
    }

    /**
     * We need to align default no cache image with custom no image for using or not using watermark
     * Custom no image creates as usual product image and all rules implements for this image as for product
     * Default no image generates during first page loading, and we are not generating it by command,
     * so after resize command started, we remove all cached no image, and it will regenerates while first
     * page loading according to the rules (e.g. watermark)
     */
    protected function removeNoImagesCache(OutputInterface $output): void
    {
        if (!empty($this->noImagesPath)) {
            $noImagesWebpPaths = array_map(fn (string $path) => sprintf('%s.webp', $path), $this->noImagesPath);
            $this->cacheManager->remove($this->noImagesPath);
            $this->cacheManager->remove($noImagesWebpPaths);
            $output->writeln('Default no_image images were removed.');
        }
    }
}

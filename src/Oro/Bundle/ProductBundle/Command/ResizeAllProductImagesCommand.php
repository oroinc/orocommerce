<?php

namespace Oro\Bundle\ProductBundle\Command;

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
 * Adds to message queue the jobs for resizing all product images (ensure the oro:message-queue:consume command
 * is running to get images resized)
 */
class ResizeAllProductImagesCommand extends Command
{
    private const OPTION_FORCE = 'force';
    private const DIMENSION = 'dimension';
    private const BATCH_SIZE = 1000;

    /** @var string */
    protected static $defaultName = 'product:image:resize-all';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(DoctrineHelper $doctrineHelper, EventDispatcherInterface $eventDispatcher)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption(self::OPTION_FORCE, null, null, 'Overwrite existing images')
            ->addOption(
                self::DIMENSION,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'perform resize of given dimension(s)',
                []
            )
            ->setDescription(
                <<<DESC
Resize All Product Images (the command only adds jobs to a queue, ensure the oro:message-queue:consume command 
is running to get images resized)
DESC
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $iterator = $this->getProductImagesIterator();
        $entitiesProcessed = 0;
        $forceOption = $this->getForceOption($input);

        foreach ($iterator as $productImage) {
            $this->getEventDispatcher()->dispatch(
                ProductImageResizeEvent::NAME,
                new ProductImageResizeEvent($productImage['id'], $forceOption, $this->getDimensionsOption($input))
            );
            $entitiesProcessed++;
        }

        $output->writeln(sprintf('%d product image(s) queued for resize.', $entitiesProcessed));
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * @return BufferedIdentityQueryResultIterator
     */
    protected function getProductImagesIterator(): BufferedIdentityQueryResultIterator
    {
        $queryBuilder = $this->doctrineHelper
            ->getEntityRepositoryForClass(ProductImage::class)
            ->createQueryBuilder('productImage');

        $identifierName = $this->doctrineHelper->getSingleEntityIdentifierFieldName(ProductImage::class);
        $queryBuilder->select("productImage.$identifierName as id");

        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);

        return $iterator;
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    protected function getForceOption(InputInterface $input): bool
    {
        return (bool)$input->getOption(self::OPTION_FORCE);
    }

    /**
     * @param InputInterface $input
     * @return string[]|null
     */
    protected function getDimensionsOption(InputInterface $input)
    {
        return $input->getOption(self::DIMENSION);
    }
}

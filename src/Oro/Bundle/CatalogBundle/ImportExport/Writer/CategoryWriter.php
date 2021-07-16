<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Writer;

use Doctrine\DBAL\Exception\RetryableException;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\TreeListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer;
use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;

/**
 * Extends regular entity writer:
 * - disables Gedmo TreeListener before persisting and flushing
 * - fills left, right, level columns of Gedmo tree after entities are flushed
 */
class CategoryWriter extends EntityWriter
{
    /** @var TreeListener */
    private $treeListener;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityDetachFixer $detachFixer,
        ContextRegistry $contextRegistry,
        TreeListener $treeListener
    ) {
        $this->treeListener = $treeListener;

        parent::__construct($doctrineHelper, $detachFixer, $contextRegistry);
    }

    /**
     * {@inheritdoc}
     *
     * Disables Gedmo TreeListener before persisting and flushing.
     */
    public function write(array $items)
    {
        try {
            $this->treeListener->setEnabled(false);

            parent::write($items);

            $this->recoverGedmoTree();
        } catch (RetryableException $e) {
            $context = $this->contextRegistry->getByStepExecution($this->stepExecution);
            $context->setValue('deadlockDetected', true);
        } finally {
            $this->treeListener->setEnabled(true);
        }
    }

    private function recoverGedmoTree(): void
    {
        // Fills left, right, level columns of Gedmo tree.
        $this->doctrineHelper
            ->getEntityRepositoryForClass(Category::class)
            ->recover();

        $this->doctrineHelper
            ->getEntityManagerForClass(Category::class)
            ->flush();
    }
}

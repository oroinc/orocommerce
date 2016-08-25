<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;

class OrmIndexerStub implements IndexerInterface
{
    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, array $context = [])
    {
        return 12;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, array $context = [])
    {
        throw new \BadMethodCallException('Method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, array $context = [])
    {
        throw new \BadMethodCallException('Method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null, array $context = [])
    {
        throw new \BadMethodCallException('Method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        throw new \BadMethodCallException('Method not implemented');
    }
}

<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;

abstract class AbstractCategoryCaseAction extends AbstractAction
{
    /**
     * @var CategoryCaseCacheBuilderInterface
     */
    protected $cacheBuilder;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!$this->cacheBuilder) {
            throw new \InvalidArgumentException('CacheBuilder is not provided');
        }

        if (!$this->registry) {
            throw new \InvalidArgumentException('Registry is not provided');
        }

        return $this;
    }

    /**
     * @param CategoryCaseCacheBuilderInterface $cacheBuilder
     */
    public function setCacheBuilder(CategoryCaseCacheBuilderInterface $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * @param Registry $registry
     */
    public function setRegistry(Registry $registry)
    {
        $this->registry = $registry;
    }
}

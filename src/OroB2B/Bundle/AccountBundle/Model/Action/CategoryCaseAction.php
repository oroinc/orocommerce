<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\CacheBuilder;

abstract class CategoryCaseAction extends AbstractAction
{
    /**
     * @var CacheBuilder
     */
    protected $cacheBuilder;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!$this->cacheBuilder) {
            throw new \InvalidArgumentException('CacheBuilder is not provided');
        }

        return $this;
    }

    /**
     * @param CacheBuilder $cacheBuilder
     */
    public function setCacheBuilder(CacheBuilder $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;
    }
}

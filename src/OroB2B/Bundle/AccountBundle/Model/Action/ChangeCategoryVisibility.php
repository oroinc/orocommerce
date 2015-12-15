<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

class ChangeCategoryVisibility extends AbstractAction
{
    /**
     * @var CacheBuilderInterface
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
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function setCacheBuilder(CacheBuilderInterface $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $categoryVisibility = $context->getEntity();

        $this->cacheBuilder->resolveVisibilitySettings($categoryVisibility);
    }
}

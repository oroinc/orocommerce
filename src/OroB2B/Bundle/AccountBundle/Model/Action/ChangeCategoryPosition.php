<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;

class ChangeCategoryPosition extends AbstractAction
{
    /**
     * @var CategoryCaseCacheBuilderInterface
     */
    protected $cacheBuilder;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!$this->cacheBuilder) {
            throw new \InvalidArgumentException('CacheBuilder for category position change is not provided');
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
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $category = $context->getEntity();

        $this->cacheBuilder->categoryPositionChanged($category);
    }
}

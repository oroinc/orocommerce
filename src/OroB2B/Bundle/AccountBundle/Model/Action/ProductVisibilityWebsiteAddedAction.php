<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

class ProductVisibilityWebsiteAddedAction extends AbstractAction
{
    /**
     * @var CacheBuilderInterface
     */
    protected $cacheBuilder;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $this->cacheBuilder->buildCache($context->getEntity());
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
    }

    /**
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function setCacheBuilder(CacheBuilderInterface $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;
    }
}

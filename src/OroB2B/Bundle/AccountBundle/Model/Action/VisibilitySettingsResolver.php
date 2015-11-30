<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

class VisibilitySettingsResolver extends AbstractAction
{
    /** @var CacheBuilderInterface */
    public $cacheBuilder;

    /**
     * @param ContextAccessor $contextAccessor
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function __construct(ContextAccessor $contextAccessor, CacheBuilderInterface $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;
        parent::__construct($contextAccessor);
    }

    /**
     * @param ProcessData $context
     */
    protected function executeAction($context)
    {

        $this->cacheBuilder->resolveVisibilitySettings($context->getEntity());
    }

    public function initialize(array $options)
    {
        if (empty($options['visibility_entity'])) {
            throw new InvalidParameterException('visibility_entity parameter is required');
        }
    }
}

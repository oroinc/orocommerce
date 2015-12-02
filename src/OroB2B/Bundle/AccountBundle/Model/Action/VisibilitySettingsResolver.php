<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

class VisibilitySettingsResolver extends AbstractAction
{
    /** @var CacheBuilderInterface */
    protected $cacheBuilder;

    /** @var bool */
    protected $deleted;

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
        /** @var VisibilityInterface $visibility */
        $visibility = $context->getEntity();
        if ($this->deleted) {
            $visibility->setVisibility($visibility::getDefault($visibility));
        }
        $this->cacheBuilder->resolveVisibilitySettings($visibility);
    }

    public function initialize(array $options)
    {
        if (empty($options['visibility_entity'])) {
            throw new InvalidParameterException('visibility_entity parameter is required');
        }
        $this->deleted = isset($options['deleted']) && $options['deleted'];
    }
}

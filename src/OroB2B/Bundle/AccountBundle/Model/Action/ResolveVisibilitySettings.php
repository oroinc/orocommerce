<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

class ResolveVisibilitySettings extends AbstractAction
{
    /** @var CacheBuilderInterface */
    protected $cacheBuilder;

    /** @var bool */
    protected $resetVisibility = false;

    /**
     * Empty constructor, no extra dependencies
     */
    public function __construct()
    {
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
        if (!$context instanceof ProcessData) {
            throw new \LogicException('This action can be called only from process context');
        }

        $visibility = $context->getEntity();
        if (!$visibility instanceof VisibilityInterface) {
            throw new \LogicException('Resolvable entity must implement VisibilityInterface');
        }

        if ($this->resetVisibility) {
            $visibility->setVisibility($visibility::getDefault($visibility));
        }

        $this->cacheBuilder->resolveVisibilitySettings($visibility);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->resetVisibility = array_key_exists('reset_visibility', $options) && $options['reset_visibility'];
    }
}

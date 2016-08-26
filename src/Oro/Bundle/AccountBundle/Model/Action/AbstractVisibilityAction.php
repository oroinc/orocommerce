<?php

namespace Oro\Bundle\AccountBundle\Model\Action;

use Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

abstract class AbstractVisibilityAction extends AbstractEntityAwareAction
{
    /**
     * @var CacheBuilderInterface
     */
    protected $cacheBuilder;

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
    public function initialize(array $options)
    {
        if (!$this->cacheBuilder) {
            throw new \InvalidArgumentException('CacheBuilder is not provided');
        }

        return parent::initialize($options);
    }
}

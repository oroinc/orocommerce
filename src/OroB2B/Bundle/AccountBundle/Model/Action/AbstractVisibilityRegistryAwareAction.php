<?php

namespace Oro\Bundle\AccountBundle\Model\Action;

use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class AbstractVisibilityRegistryAwareAction extends AbstractVisibilityAction
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!$this->registry) {
            throw new \InvalidArgumentException('Registry is not provided');
        }

        return parent::initialize($options);
    }

    /**
     * @param RegistryInterface $registry
     */
    public function setRegistry(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }
}

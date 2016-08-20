<?php

namespace Oro\Bundle\AccountBundle\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Action\Action\AbstractAction;

abstract class AbstractEntityAwareAction extends AbstractAction
{
    /**
     * @var PropertyPathInterface|object|null
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('entity', $options)) {
            $this->entity = $options['entity'];
        } elseif (array_key_exists(0, $options)) {
            $this->entity = $options[0];
        }

        return $this;
    }

    /**
     * @param mixed $context
     * @return mixed|null|object
     */
    protected function getEntity($context)
    {
        if ($this->entity) {
            return $this->contextAccessor->getValue($context, $this->entity);
        } elseif ($context instanceof ProcessData) {
            return $context->getEntity();
        }

        return null;
    }
}

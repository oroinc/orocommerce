<?php

namespace Oro\Bundle\ActionBundle\Action;

use Oro\Bundle\ActionBundle\Exception\InvalidParameterException;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;

class RefreshGrid extends AbstractAction
{
    /**
     * @var string
     */
    protected $gridName;

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $this->contextAccessor->setValue($context, 'refreshGrid', $this->gridName);
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (count($options) !== 1 || empty($options[0])) {
            throw new InvalidParameterException('Gridname parameter must be specified');
        }

        $this->gridName = $options[0];

        return $this;
    }
}

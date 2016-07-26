<?php

namespace OroB2B\Bundle\CheckoutBundle\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;

class SaveCheckoutStateAction extends AbstractAction
{
    const OPTION_KEY_STATE = 'state';
    const OPTION_KEY_SAVE_TO = 'saveTo';
    const OPTION_KEY_TOKEN = 'tokenAttribute';

    /** @var array */
    protected $options;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $statePath = $this->getOption($this->options, self::OPTION_KEY_STATE);
        $saveToPath = $this->getOption($this->options, self::OPTION_KEY_SAVE_TO);
        $tokenPath = $this->getOption($this->options, self::OPTION_KEY_TOKEN);

        $stateValue = $this->contextAccessor->getValue($context, $statePath);
        $saveToValue = $this->contextAccessor->getValue($context, $saveToPath);

        if (!is_array($saveToValue)) {
            throw new InvalidParameterException(
                sprintf(
                    'Parameter "%s" must have property path to array. "%s" is not array',
                    self::OPTION_KEY_SAVE_TO,
                    $saveToPath
                )
            );
        }

        $token = UUIDGenerator::v4();
        $saveToValue[$token] = $stateValue;

        $this->contextAccessor->setValue($context, $saveToPath, $saveToValue);
        $this->contextAccessor->setValue($context, $tokenPath, $token);

    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_STATE])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', self::OPTION_KEY_STATE));
        }

        if (empty($options[self::OPTION_KEY_SAVE_TO])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', self::OPTION_KEY_SAVE_TO));
        }

        $this->options = $options;

        return $this;
    }
}

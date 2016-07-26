<?php

namespace OroB2B\Bundle\CheckoutBundle\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;

class GetCheckoutStateAction extends AbstractAction
{
    const OPTION_KEY_ATTRIBUTE = 'attribute';
    const OPTION_KEY_GET_FROM = 'getFrom';
    const OPTION_KEY_TOKEN = 'token';

    /** @var array */
    protected $options;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $attributePath = $this->getOption($this->options, self::OPTION_KEY_ATTRIBUTE);
        $getFromPath = $this->getOption($this->options, self::OPTION_KEY_GET_FROM);
        $tokenPath = $this->getOption($this->options, self::OPTION_KEY_TOKEN);

        $stateStorage = $this->contextAccessor->getValue($context, $getFromPath);

        if (!is_array($stateStorage)) {
            throw new InvalidParameterException(
                sprintf(
                    'Parameter "%s" must have property path to array. "%s" is not array',
                    self::OPTION_KEY_GET_FROM,
                    $getFromPath
                )
            );
        }

        $token = $this->contextAccessor->getValue($context, $tokenPath);

        $state = null;
        if (array_key_exists($token, $stateStorage)) {
            $state = $stateStorage[$token];
        }

        $this->contextAccessor->setValue($context, $attributePath, $state);

    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_TOKEN])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', self::OPTION_KEY_TOKEN));
        }

        if (empty($options[self::OPTION_KEY_GET_FROM])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', self::OPTION_KEY_GET_FROM));
        }

        if (empty($options[self::OPTION_KEY_ATTRIBUTE])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', self::OPTION_KEY_ATTRIBUTE));
        }

        $this->options = $options;

        return $this;
    }
}

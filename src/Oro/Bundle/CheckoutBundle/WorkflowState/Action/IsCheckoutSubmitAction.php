<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Action;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks whatever it is POST method executed
 *
 * Usage:
 * @is_checkout_submit:
 *      attribute: $.result.is_submit
 */
class IsCheckoutSubmitAction extends AbstractAction
{
    const OPTION_KEY_ATTRIBUTE = 'attribute';

    /**
     * @var array
     */
    private $options;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    public function __construct(ContextAccessor $contextAccessor, RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        parent::__construct($contextAccessor);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $attributePath = $this->getOption($this->options, self::OPTION_KEY_ATTRIBUTE);

        $isPost = $this->requestStack->getCurrentRequest()->isMethod(Request::METHOD_POST);

        $this->contextAccessor->setValue($context, $attributePath, $isPost);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->throwExceptionIfRequiredParameterEmpty($options, self::OPTION_KEY_ATTRIBUTE);

        $this->options = $options;

        return $this;
    }

    /**
     * @param array $options
     * @param string $parameter
     * @throws InvalidParameterException
     */
    protected function throwExceptionIfRequiredParameterEmpty($options, $parameter)
    {
        if (empty($options[$parameter])) {
            throw new InvalidParameterException(sprintf('Parameter "%s" is required', $parameter));
        }
    }
}

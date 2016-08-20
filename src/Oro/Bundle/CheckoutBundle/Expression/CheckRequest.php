<?php

namespace Oro\Bundle\CheckoutBundle\Expression;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

class CheckRequest extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'check_request';
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->options, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->options = array_merge(
            [
                'is_ajax' => null,
                'expected_key' => null,
                'expected_value' => null
            ],
            $options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $this->convertToArray($this->options);
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        $isAjax = $this->resolveValue($context, $this->options['is_ajax'], false);
        if ($isAjax !== null) {
            if ($request->isXmlHttpRequest() !== $isAjax) {
                return false;
            }
        }

        $expectedKey = $this->resolveValue($context, $this->options['expected_key'], false);
        $expectedValue = $this->resolveValue($context, $this->options['expected_value'], false);
        if ($request->get($expectedKey) != $expectedValue) {
            return false;
        }

        return true;
    }
}

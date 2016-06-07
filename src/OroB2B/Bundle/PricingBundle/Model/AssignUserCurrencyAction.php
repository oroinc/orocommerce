<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

/**
 * Assign user selected currency to selected attribute. Applicable only for frontend
 * Usage:
 *
 * @assign_user_currency: $.selectedCurrency
 *
 * Or
 *
 * @assign_user_curreny:
 *     attribute: $.selectedCurrency
 */
class AssignUserCurrencyAction extends AbstractAction
{
    /**
     * @var PropertyPathInterface
     */
    protected $attribute;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @param ContextAccessor $contextAccessor
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(ContextAccessor $contextAccessor, UserCurrencyManager $currencyManager)
    {
        parent::__construct($contextAccessor);

        $this->currencyManager = $currencyManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $this->contextAccessor->setValue($context, $this->attribute, $this->currencyManager->getUserCurrency());
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (count($options) !== 1) {
            throw new InvalidParameterException('Only one attribute parameter must be defined');
        }

        $attribute = null;
        if (array_key_exists(0, $options)) {
            $attribute = $options[0];
        } elseif (array_key_exists('attribute', $options)) {
            $attribute = $options['attribute'];
        }

        if (!$attribute) {
            throw new InvalidParameterException('Attribute must be defined');
        }
        if (!$attribute instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        $this->attribute = $attribute;

        return $this;
    }
}

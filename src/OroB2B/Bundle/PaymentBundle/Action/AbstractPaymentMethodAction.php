<?php

namespace OroB2B\Bundle\PaymentBundle\Action;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

abstract class AbstractPaymentMethodAction extends AbstractAction
{
    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /** @var OptionsResolver */
    private $optionsResolver;

    /** @var OptionsResolver */
    private $valuesResolver;

    /** @var object */
    protected $entity;

    /** @var array */
    protected $options;

    /**
     * @param ContextAccessor $contextAccessor
     * @param PaymentMethodRegistry $paymentMethodRegistry
     * @param PaymentTransactionProvider $paymentTransactionProvider
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        PaymentMethodRegistry $paymentMethodRegistry,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        parent::__construct($contextAccessor);

        $this->paymentMethodRegistry = $paymentMethodRegistry;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        if (!$this->optionsResolver) {
            $this->optionsResolver = new OptionsResolver();
            $this->configureOptionsResolver($this->optionsResolver);
        }

        return $this->optionsResolver;
    }

    /**
     * @return OptionsResolver
     */
    protected function getValuesResolver()
    {
        if (!$this->valuesResolver) {
            $this->valuesResolver = new OptionsResolver();
            $this->configureValuesResolver($this->optionsResolver);
        }

        return $this->optionsResolver;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptionsResolver(OptionsResolver $resolver)
    {
        $normalizer = function (OptionsResolver $resolver, $value) {
            if (is_string($value)) {
                return new PropertyPath($value);
            }

            return $value;
        };

        $allowedTypes = ['string', 'Symfony\Component\PropertyAccess\PropertyPathInterface'];

        $resolver
            ->setRequired(['object', 'amount', 'currency'])
            ->addAllowedTypes('object', $allowedTypes)
            ->addAllowedTypes('amount', $allowedTypes)
            ->addAllowedTypes('currency', $allowedTypes)
            ->setNormalizer('object', $normalizer)
            ->setNormalizer('amount', $normalizer)
            ->setNormalizer('currency', $normalizer);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureValuesResolver(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['object', 'amount', 'currency'])
            ->addAllowedTypes('object', 'object')
            ->addAllowedTypes('amount', 'string')
            ->addAllowedTypes('currency', 'string');
    }

    /**
     * @param mixed $context
     * @return array
     */
    public function getOptions($context)
    {
        $values = [];

        $definedOptions = $this->getOptionsResolver()->getDefinedOptions();
        foreach ($definedOptions as $definedOption) {
            $values[$definedOption] = $this->contextAccessor->getValue($context, $this->options[$definedOption]);
        }

        return $this->getValuesResolver()->resolve($values);
    }

    /** {@inheritdoc} */
    public function initialize(array $options)
    {
        $this->options = $this->getOptionsResolver()->resolve($options);
    }
}

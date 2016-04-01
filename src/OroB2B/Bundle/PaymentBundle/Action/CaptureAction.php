<?php

namespace OroB2B\Bundle\PaymentBundle\Action;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Model\ContextAccessor;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class CaptureAction extends AbstractPaymentMethodAction
{
    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /** @var OptionsResolver */
    private $optionsResolver;

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
            $this->optionsResolver
                ->setRequired('object')
                ->addAllowedTypes('object', ['string', 'Symfony\Component\PropertyAccess\PropertyPathInterface'])
                ->setNormalizer(
                    'object',
                    function (OptionsResolver $resolver, $value) {
                        if (is_string($value)) {
                            return new PropertyPath($value);
                        }

                        return $value;
                    }
                );
        }

        return $this->optionsResolver;
    }

    /** {@inheritdoc} */
    protected function executeAction($context)
    {
        $object = $this->contextAccessor->getValue($context, $this->options['object']);
        if (!$object) {
            return;
        }

        $paymentTransaction = $this->paymentTransactionProvider->getPaymentTransaction($object);
        if (!$paymentTransaction) {
            return;
        }

        $this->paymentMethodRegistry
            ->getPaymentMethod($paymentTransaction->getType())
            ->action(PaymentMethodInterface::CAPTURE, $paymentTransaction->getData());
    }

    /** {@inheritdoc} */
    public function initialize(array $options)
    {
        $this->options = $this->getOptionsResolver()->resolve($options);
    }
}

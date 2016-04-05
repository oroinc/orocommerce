<?php

namespace OroB2B\Bundle\PaymentBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use OroB2B\Bundle\PaymentBundle\Form\PaymentMethodTypeRegistry;

class PaymentMethodsProvider implements DataProviderInterface
{
    const NAME = 'orob2b_payment_methods_provider';

    /**
     * @var FormAccessor
     */
    protected $data = null;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var PaymentMethodTypeRegistry
     */
    protected $registry;

    /**
     * @param FormFactoryInterface $formFactory
     * @param PaymentMethodTypeRegistry $registry
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        PaymentMethodTypeRegistry $registry
    ) {
        $this->formFactory = $formFactory;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (null === $this->data) {
            $this->data = [];

            $types = $this->registry->getPaymentMethodTypes();
            foreach ($types as $type) {
                if ($type->isMethodEnabled()) {
                    $name = $type->getName();
                    $form =  $this->formFactory->create($name, [], []);
                    $this->data[$name] = $form->createView();
                }
            }
        }
        return $this->data;
    }
}

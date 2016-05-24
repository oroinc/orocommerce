<?php

namespace OroB2B\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\AddressExtractor;

class PaymentMethodsProvider implements DataProviderInterface
{
    const NAME = 'orob2b_payment_methods_provider';

    /**
     * @var array[]
     */
    protected $data;

    /** @var PaymentMethodViewRegistry */
    protected $registry;

    /** @var AddressExtractor */
    private $addressExtractor;

    /**
     * @param PaymentMethodViewRegistry $registry
     * @param AddressExtractor $addressExtractor
     */
    public function __construct(PaymentMethodViewRegistry $registry, AddressExtractor $addressExtractor)
    {
        $this->registry = $registry;
        $this->addressExtractor = $addressExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return self::NAME;
    }


    /** {@inheritdoc} */
    public function getData(ContextInterface $context)
    {
        if (null === $this->data) {
            $contextData = $this->processContext($context);

            $views = $this->registry->getPaymentMethodViews($contextData);
            foreach ($views as $name => $view) {
                $this->data[$name] = [
                    'label' => $view->getLabel(),
                    'block' => $view->getBlock(),
                    'options' => $view->getOptions($contextData),
                ];
            }
        }

        return $this->data;
    }

    /**
     * @param ContextInterface $context
     * @return array
     */
    protected function processContext(ContextInterface $context)
    {
        $entity = $this->getEntity($context);
        $countryCode = $this->addressExtractor->getCountryIso2($entity);

        return [
            'entity' => $entity,
            'country' => $countryCode,
        ];
    }

    /**
     * @param ContextInterface $context
     * @return object|null
     */
    protected function getEntity(ContextInterface $context)
    {
        $entity = null;
        $contextData = $context->data();
        if ($contextData->has('entity')) {
            $entity = $contextData->get('entity');
        }

        if (!$entity && $contextData->has('checkout')) {
            $entity = $contextData->get('checkout');
        }

        return $entity;
    }
}

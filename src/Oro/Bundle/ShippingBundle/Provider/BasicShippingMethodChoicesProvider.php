<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class BasicShippingMethodChoicesProvider implements ShippingMethodChoicesProviderInterface
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ShippingMethodRegistry $methodRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(ShippingMethodRegistry $methodRegistry, TranslatorInterface $translator)
    {
        $this->methodRegistry = $methodRegistry;
        $this->translator = $translator;
    }
    /**
     * {@inheritdoc}
     */
    public function getMethods($translate = false)
    {
        return array_reduce(
            $this->methodRegistry->getShippingMethods(),
            function (array $result, ShippingMethodInterface $method) use ($translate) {
                $label = $method->getLabel();
                if ($translate) {
                    $label = $this->translator->trans($label);
                }
                $result[$method->getIdentifier()] = $label;
                return $result;
            },
            []
        );
    }
}

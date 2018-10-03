<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;

/**
 * Provider allows to use PaymentMethodObject's getters from email templates
 */
class PaymentMethodObjectVariablesProvider implements EntityVariablesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions($class = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableGetters($class = null)
    {
        return [PaymentMethodObject::class => ['getLabel', 'getOptions']];
    }
}

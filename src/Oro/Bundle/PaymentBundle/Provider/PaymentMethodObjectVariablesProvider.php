<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;

/**
 * The provider that allows to use PaymentMethodObject in email templates.
 */
class PaymentMethodObjectVariablesProvider implements EntityVariablesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableGetters(): array
    {
        return [
            PaymentMethodObject::class => [
                'label'   => 'getLabel',
                'options' => 'getOptions'
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableProcessors(string $entityClass): array
    {
        return [];
    }
}

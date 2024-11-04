<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;

/**
 * The provider that allows to use PaymentMethodObject in email templates.
 */
class PaymentMethodObjectVariablesProvider implements EntityVariablesProviderInterface
{
    #[\Override]
    public function getVariableDefinitions(): array
    {
        return [];
    }

    #[\Override]
    public function getVariableGetters(): array
    {
        return [
            PaymentMethodObject::class => [
                'label'   => 'getLabel',
                'options' => 'getOptions'
            ]
        ];
    }

    #[\Override]
    public function getVariableProcessors(string $entityClass): array
    {
        return [];
    }
}

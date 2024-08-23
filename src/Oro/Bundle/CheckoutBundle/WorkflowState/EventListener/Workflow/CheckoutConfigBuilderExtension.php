<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\EventListener\Workflow;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Add state_token to workflow attributes and continue transition forms if checkout state protection is enabled.
 */
class CheckoutConfigBuilderExtension implements WorkflowDefinitionBuilderExtensionInterface
{
    public function prepare($workflowName, array $configuration)
    {
        if (empty($configuration['metadata']['is_checkout_workflow'])) {
            return $configuration;
        }

        if (empty($configuration['metadata']['checkout_state_config']['enable_state_protection'])) {
            return $configuration;
        }

        if (empty($configuration['attributes']['state_token'])) {
            $configuration['attributes']['state_token'] = [
                'type' => 'string',
                'label' => 'oro.workflow.checkout.state_token.attribute_label'
            ];
        }

        foreach ($configuration['transitions'] as &$transition) {
            if (empty($transition['frontend_options']['is_checkout_continue'])) {
                continue;
            }
            if (empty($transition['form_options'])) {
                continue;
            }
            if (!empty($transition['form_options']['attribute_fields']['state_token'])) {
                continue;
            }

            $transition['form_options']['attribute_fields']['state_token'] = [
                'form_type' => HiddenType::class,
                'label' => 'oro.workflow.checkout.state_token.attribute_label'
            ];
        }

        return $configuration;
    }
}

# Add the Agreements Step to a Custom Checkout Based on the Default Checkout Workflow (Example)

## Agreements Step

Add the Agreements step to the `acme_demo_checkout` workflow with the `customer_consents` name and the `continue_to_billing_address` allowed transition. For all other steps, add the `verify_customer_consents` allowed transition. `verify_customer_consents` helps to redirect to the `customer_consents` step if some consents were not accepted.

```yml
workflows:
    acme_demo_checkout:
        steps:
            # ...
            customer_consents:
                order: 20
                allowed_transitions:
                    - continue_to_billing_address
            enter_billing_address:
                order: 30
                allowed_transitions:
                    - verify_customer_consents
                    # ...
            enter_shipping_address:
                order: 40
                allowed_transitions:
                    - verify_customer_consents
                    # ...
            # ...
```

## Transitions

Next, add the `continue_to_billing_address` transition to the Agreements page and the `verify_customer_consents` transition that check that all mandatory consents are accepted. The **stop_propagation** option enables you to check the required consents before every step and transit the workflow to the `customer_consents` step.

```yml
workflows:
    acme_demo_checkout:
        transitions:
            # ...
            continue_to_billing_address:
                step_to: enter_billing_address
                transition_definition: continue_to_billing_address_definition
                display_type: page
                frontend_options:
                    is_checkout_continue: true
                    is_checkout_show_errors: true
                form_options:
                    attribute_fields:
                        customerConsents:
                            form_type: Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType
                            options:
                                mapped: false
                                required: true
                                property_path: customerConsents
            # ...
            verify_customer_consents:
                step_to: customer_consents
                transition_definition: verify_customer_consents_definition
                is_hidden: true
                frontend_options:
                    stop_propagation: true
            # ...
```

## Transition Definitions

For the above transitions, add transition definitions that save consents and check that all required consents are accepted.

```yml
workflows:
    acme_demo_checkout:
        transition_definitions:
            # ...
            continue_to_billing_address_definition:
                preactions:
                    - '@assign_value': [$consents_available, true]
                actions:
                    - '@save_accepted_consents':
                            acceptedConsents: $customerConsents
            # ...
            verify_customer_consents_definition:
                preconditions:
                    '@not':
                        - '@is_consents_accepted':
                            acceptedConsents: $customerConsents
                preactions:
                    - '@flash_message':
                        conditions:
                            '@and':
                                - '@not':
                                    - '@is_consents_accepted':
                                        acceptedConsents: $customerConsents
                                - '@equal': [$consents_available, true]
                        message: oro.checkout.workflow.condition.required_consents_should_be_checked.message
                        type: 'warning'
            # ...
```

## Template

To show a block with the consent items:

1. Import a layout with the consent items and configure it.

```yml
layout:
    imports:
        -
            id: oro_consent_items
            namespace: checkout
            root: checkout_consent_container

    actions:
        - '@setBlockTheme':
            themes: 'consents.html.twig'

        - '@add':
            id: checkout_consent_container
            blockType: container
            parentId: checkout_form
            prepend: true

        - '@add':
            id: checkout_consent_message
            blockType: consent_acceptance_choice
            parentId: checkout_consent_container
```

2. Customize the templates, as illustrated below.

```twig
{% block _checkout_form_fields_widget %}
    {% if form.customerConsents is defined %}{{ form_widget(form.customerConsents) }}{% endif %}
{% endblock %}

{% block _checkout_consent_container_widget %}
    <div {{ block('block_attributes') }}>
        <div class="grid__column">
            {{ parent_block_widget(block) }}
        </div>
    </div>
{% endblock %}

{% block _checkout_consent_message_widget %}
    {% set attr = layout_attr_defaults(attr, {
        'class': 'notification notification--success'
    }) %}

    {% if consents is empty %}
        <div {{ block('block_attributes') }}>
            <span class="notification__item">
                <i class="fa-check"></i> {{ 'oro.consent.frontend.checkout.form.messages.all_agreements_accepted'|trans }}
            </span>
        </div>
    {% endif %}
{% endblock %}
```

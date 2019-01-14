# Example how to add "Agreements" step to custom checkout workflow based on "Default Checkout"

## "Agreements" step

At first, let's add "Agreements" step to `acme_demo_checkout` workflow with name `customer_consents` and allowed transition `continue_to_billing_address`. For all others steps we should add `verify_customer_consents` allowed transition. `verify_customer_consents` helps to redirect to `customer_consents` step if some of consents was not accepted.

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

Next let's add `continue_to_billing_address` transition for "Agreements" page, and `verify_customer_consents` transition that check that all required consents is accepted. **stop_propagation** option gives us the opportunity to check required consents before every step and transit workflow to `customer_consents` step.

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

## Transition definitions

For the above transitions let's add transition definitions that save consents and check that all required consents is accepted.

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

In order to show block with consent items, import layout with consent items and configure it

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

and customize templates

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

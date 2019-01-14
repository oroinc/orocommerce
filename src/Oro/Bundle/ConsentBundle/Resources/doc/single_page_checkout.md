# Example how to add "Agreements" section to custom checkout workflow based on "Single Page Checkout"

## "Agreements" section

Let's add "Agreements" section to `acme_demo_checkout_single_page`. Extends `save_state` and `create_order` with `customerConsents` attribute field in order to display the section.

```yml
workflows:
    acme_demo_checkout_single_page:
        transitions:
            # ...
            save_state:
                # ...
                form_options:
                    # ...
                    attribute_fields:
                        # ...
                        customerConsents:
                            form_type: Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType
                            options:
                                required: true
                                property_path: customerConsents
                        # ...
            # ...
            create_order:
                # ...
                form_options:
                    # ...
                    attribute_fields:
                        # ...
                        customerConsents:
                            form_type: Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType
                            options:
                                required: true
                                property_path: customerConsents
                       # ...
```

and add transition definitions that save consents and check that all required consents is accepted.

```yml
workflows:
    acme_demo_checkout_single_page:
        transition_definitions:
            # ...
            save_state_definition:
                  # ...
                  - '@assign_value':
                      conditions:
                          '@not':
                              - '@is_consents_accepted':
                                  acceptedConsents: $customerConsents
                      parameters: [$consents_available, true]

                  - '@save_accepted_consents':
                      acceptedConsents: $customerConsents
                  # ...
            # ...
            create_order_transition_definition:
                # ...
                conditions:
                    '@and':
                        # ...
                        - '@is_consents_accepted':
                            acceptedConsents: $customerConsents
                            message: oro.checkout.workflow.condition.required_consents_should_be_checked_on_single_page_checkout.message
                            type: 'warning'
                        # ...
                # ...
                actions:
                    # ...
                    - '@save_accepted_consents':
                        acceptedConsents: $customerConsents
                    # ...
```

## Template

In order to show block with consent items, import layout with consent items and configure it

```yml
layout:
    imports:
        # ...
        -
            id: oro_consent_items
            namespace: checkout
            root: checkout_consent_container
    actions:
        # ...
        - '@add':
           id: checkout_consent_container
           blockType: container
           parentId: checkout_order_summary_totals_container
           siblingId: checkout_order_summary_totals_sticky_container
           prepend: true

        - '@add':
           id: checkout_consent_message
           blockType: consent_acceptance_choice
           parentId: checkout_consent_container
```

and customize templates

```twig
{% block _checkout_consent_container_widget %}
    <div {{ block('block_attributes') }}>
        <span class="label label--full text-uppercase">
            {{- 'oro.consent.frontend.checkout.form.sections.data_protection.label'|trans -}}
        </span>
        {{ parent_block_widget(block) }}
    </div>
{% endblock %}

{% block _checkout_consent_message_widget %}
    {% set attr = layout_attr_defaults(attr, {
        'class': 'notification notification--success'
    }) %}

    {% if consents is empty %}
        <div {{ block('block_attributes') }}>
            <span class="notification__item">
                <i class="fa-check"></i> {{ 'oro.consent.frontend.single_page_checkout.form.messages.all_agreements_accepted'|trans }}
            </span>
        </div>
    {% endif %}
{% endblock %}
```

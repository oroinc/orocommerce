# Example how to add customer consents field

## Mapped field

If entity contains `customerUser` property, we can add `customerConsents` field to the form using `property_path` in the `form_options`.

```php
$form->add(
    ConsentAcceptanceType::TARGET_FIELDNAME,
    ConsentAcceptanceType::class,
    [
        'property_path' => 'customerUser.acceptedConsents',
        'constraints' => [
            new RemovedLandingPages(),
            new RemovedConsents(),
            new RequiredConsents()
        ]
    ]
);
```

## Not-mapped field

If entity doesn't contains `customerUser` property, we should add `customerConsents` field to the form and set `mapped = false` in the `form_options`.

```php
$form->add(
    ConsentAcceptanceType::TARGET_FIELDNAME,
    ConsentAcceptanceType::class,
    [
        'mapped' => false,
        'constraints' => [
            new RemovedLandingPages(),
            new RemovedConsents(),
            new RequiredConsents()
        ]
    ]
);
```

then create form listener service

```yml
acme_demo.event_listener.form_listener:
    class: 'Acme\Bundle\DemoBundle\EventListener\BeforeFlushFormListener'
    lazy: true
    tags:
        - { name: kernel.event_listener, event: oro.form.update_handler.before_entity_flush.__FORM_NAME__, method: beforeFlush }
        - { name: oro_featuretogle.feature, feature: consents }
```

and implement logic on before flush event

```php
<?php

namespace Acme\Bundle\DemoBundle\EventListener;

use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

class BeforeFlushFormListener
{
    use FeatureCheckerHolderTrait;

    /**
     * @param AfterFormProcessEvent $event
     */
    public function beforeFlush(AfterFormProcessEvent $event)
    {
        // No actions if consents feature disabled
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $formData = $event->getData();

        if ($formData instanceof Request) {
            $customerUser = $formData->getCustomerUser();
            if ($customerUser && $customerUser->isGuest()) {
                $form = $event->getForm();
                $acceptedConsents = $form->get(ConsentAcceptanceType::TARGET_FIELDNAME)->getData();

                $customerUser->setAcceptedConsents($acceptedConsents);
            }
        }
    }
}
```

## Render form field on the front store

First of all check that `customerConsents` is rendered in ther form template, as a result the input with type `hidden` will be rendered on the page.

```twig
{% if form.customerConsents is defined %}
    {{ form_widget(form.customerConsents) }}
{% endif %}
```

In order to show block with consent items, let's import layout with consent items and configure it.

```yml
layout:
    imports:
        -
            id: oro_consent_items
            root: consent_container

    actions:
        - '@setBlockTheme':
            themes: 'consents.html.twig'

        - '@add':
            id: consent_container
            blockType: container
            parentId: __PARENT_BLOCK_ID__

        - '@add':
            id: consent_message
            blockType: consent_acceptance_choice
            parentId: consent_container
```

if all consents is accepted let's add template with success message

```twig
{% block _checkout_consent_message_widget %}
    {% set attr = layout_attr_defaults(attr, {
        'class': 'notification notification--success'
    }) %}

    {% if consents is empty %}
        <div {{ block('block_attributes') }}>
            <span class="notification__item"><i class="fa-check"></i> {{ 'All mandatory consents were accepted.' }}</span>
        </div>
    {% endif %}
{% endblock %}
```

Actions Documentation
=====================

Table of Contents
-----------------
 - [What are Actions?](#what-are-actions)
 - [Main Model Classes](#main-model-classes)
 - [How it works?](#how-it-works)
 - [Configuration](#configuration)

What are Actions?
-----------------

Actions provide possibility to assign any operations to:
 - Entity classes;
 - Routes.

Every active action will show button (link) on the corresponded page(s). Button will be displayed only if all described
Pre conditions are met. Action will be performed after click on the button if all described Pre conditions
and Conditions are met.

Main Model Classes
------------------

* **Action** - main model that contains information about specific action. It contains the most important
information like action related entity classes (f.e. 'Acme\Bundle\DemoBundle\Entity\MyEntity') 
or routes ('acme_demo_myentity_view'). Action can be enabled or disabled.
Other fields of the action contain information about action name, extended options, 
order of display buttons. More options see in [Configuration](#configuration).

* **ActionDefinition** - part of the Action model that contains raw data from action's configuration.

How it works?
-------------

Each action relates to the some entity types (i.e. consists full class name) or\and routes of pages 
where action should be displayed. Before page loading Action Bundle chooses actions that 
are corresponded to page's entity\route. Then these actions checking for Pre conditions. If all Pre conditions are met 
- Action's button is displaying. After user click on the Action button - Init step (if exists) is running.
After Init step - checking all Conditions to perform Execution step - and if Conditions are met - run Execution step.

Configuration
-------------

All actions are described in configuration file ``actions.yml`` corresponded bundle. 
Look at the example of simple action configuration that performs some action with entity MyEntity.

```
actions:
    acme_demo_expire_myentity_action:                       # action name
        extends: entity_action_base                         # parent action if needed
        replace:                                            # the list of nodes that should be replaced in the parent action
            - frontend_options
        label: adme.demo.myentity.actions.myentity_action   # label for action button
        enabled: true                                       # is action enabled
        applications:                                       # for what applications action are available (backend, frontend)
            - backend
            - frontend
        entities:                                           # list of entity classes
            - Acme\Bundle\DemoBundle\Entity\MyEntity
        routes:                                             # list of routes
            - acme_demo_myentity_view    
        order: 10                                           # display order of action button
        frontend_options:                                   # display options for action button:
            icon: icon-time                                 # class of button icon
            class: btn                                      # class of button 
            template: customTemplate.html.twig              # custom button template if needed
        form_options:
            form_type: custom_form_type                     # set custom form type
            attribute_fields:                               # fields of form that will be shown on init_step
                call_timeout:
                    form_type: integer
                    options:
                        required: false
        attributes:                                         # configuration for Attributes
            ...
        preconditions:                                      # pre conditions for display Action button and run Init step
            ...
        conditions:                                         # conditions for run Execution step
            ...
        init_step:                                          # configuration for Init step
            ...
        execution_step:                                     # configuration for Execution step
            ...
```

This configuration describes action that relates to the ``MyEntity`` entity. On the View page of
this entity (acme_demo_myentity_view ) will be displayed button with label "adme.demo.myentity.actions.myentity_action".

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
- Action's button is displaying. After user click on the button - all postfunctions will be executed.

Configuration
-------------

All actions are described in configuration file ``actions.yml`` corresponded bundle. 
Look at the example of simple action configuration that performs some action with entity MyEntity.

```
actions:
    acme_demo_expire_myentity_action:                       # action name
        extends: entity_action_base                         # (optional) parent action if needed
        replace:                                            # (optional) the list of nodes that should be replaced in the parent action
            - frontend_options
        label: adme.demo.myentity.actions.myentity_action   # label for action button
        enabled: true                                       # (optional, default = true) is action enabled
        entities:                                           # (optional) list of entity classes
            - Acme\Bundle\DemoBundle\Entity\MyEntity
        routes:                                             # (optional) list of routes
            - acme_demo_myentity_view    
        order: 10                                           # (optional, default = 0) display order of action button
        frontend_options:                                   # (optional) display options for action button:
            icon: icon-time                                 # class of button icon
            class: btn                                      # class of button 
            template: customTemplate.html.twig              # custom button template if needed
        prefunctions:                                       # (optional) any needed pre functions which will execute before pre conditions
            - @create_datetime:
                attribute: $.date
        preconditions:                                      # (optional) pre conditions for display Action button
            @gt: [$updatedAt, $.date]
        postfunctions:
            - @assign_value: [$expired, true]
```

This configuration describes action that relates to the ``MyEntity`` entity. On the View page (acme_demo_myentity_view)
of this entity (in case of field 'updatedAt' > new DateTime('now')) will be displayed button with label
"adme.demo.myentity.actions.myentity_action". After click on this button - will run postfunction "assign_value" and set
field 'expired' to the value = `true`.

Configuration Reference
=======================

Table of Contents
-----------------
 - [Overview](#overview)
 - [Configuration File](#configuration-file)
 - [Configuration Loading](#configuration-loading)
 - [Configuration Merging](#configuration-merging)
 - [Defining an Action](#defining-an-action)
   - [Example](#example)

Overview
========

Configuration of Action declares all aspects related to specific action:

* basic properties of action like name, label, order, acl resource, etc
* entities or routes that is related to action
* conditions and functions
* attributes involved in action
* frontend configuration
* action widget dialog parameters

Structure of configuration is declared in class Oro\Bundle\ActionBundle\Configuration\ActionDefinitionConfiguration.

Configuration File
==================

Configuration must be placed in a file named Resources/config/actions.yml. For example
Acme/Bundle/DemoBundle/Resources/config/actions.yml.

**Example - actions.yml**
```
actions:
    acme_demo_action:
        label:  Demo Action
        entities:
            - Acme\Bundle\DemoBundle\Entity\User
        ...
```

Configuration Loading
=====================

All actions configuration load automatically on Symfony container building process. Configuration collect from all
bundles, validate and merge. Merged configuration stored in app cache.

To validate configuration manually execute a command:

```
php app/console oro:action:configuration:validate
```

Configuration Merging
=====================

All configurations merge in the boot bundles order. There are two step of merging process: overriding and extending.

**Overriding**

On this step application collect all configurations of each actions with same name and merge they to one configuration.
Merging uses simple rules:
 * if node value is scalar - value will be replaced
 * if node value is array - this array will be complemented by values from second configuration
 * if array node `replace` is exist on the same level and it contain original node name - value will be replaced

After first step application knows about all actions and have only one configuration for each action.

**Extending**
On this step application collect configurations for all actions which contain `extends`. Then main action configuration,
which specified in `extends`, copied and merged with configuration of original action. Merging use same way, which use
`overriding` step (first and second rules).

Defining an Action
==================

Root element of configuration is "actions". Under this element actions can be defined.

Single action configuration has next properties:

* **name**
    *string*
    Action should have a unique name in scope of all application.
* **extends**
    *string*
    Action name, which configuration will be used as basis for current action.
* **label**
    *string*
    This value will be shown in the UI.
* **enabled**
    *boolean*
    Flag that define whether this action is enabled. Disabled action will not used in application.
* **entities**
    *array*
    Array of entity class names. Action button will be shown on view/edit pages of this entities.
* **routes**
    *array*
    Action button will be shown on pages which route is in list.
* **order**
    *integer*
    Parameter that specifies the display order of actions buttons.
* **acl_resource**
    *string*
    Action button will be shown only if user have expected permissions.
* **frontend_options**
    Contains configuration for Frontend Options
* **prefunctions**
    Contains configuration for Pre Conditions
* **preconditions**
    Contains configuration for Pre Conditions
* **attributes**
    Contains configuration for Attributes
* **form_options**
    Contains configuration for Transitions
* **initfunctions**
    Contains configuration for Init Functions
* **conditions**
    Contains configuration for Conditions
* **postfunctions**
    Contains configuration for Post Functions

Example
-------
```
actions:                                              # root elements
    demo_action:                                      # name of action
        extends: demo_action_base                     # base action name
        label: aсme.demo.actions.myentity_action      # this value will be shown in UI for action button
        enabled: false                                # action is disabled, means not used in application
        entities:                                     # on view/edit pages of this entities action button will be shown
            - Acme\Bundle\DemoBundle\Entity\MyEntity  # entity class name
        routes:                                       # on pages with this action names action button will be shown
            - acme_demo_action_view                   # route name
        order: 10                                     # display order of action button
        acl_resource: acme_demo_action_view           # ACL resource name
        frontend_options:                             # configuration for Frontend Options
                                                      # ...
        prefunctions:                                 # configuration for Pre Functions
                                                      # ...
        preconditions:                                # configuration for Pre Conditions
                                                      # ...
        attributes:                                   # configuration for Attributes
                                                      # ...
        form_options:                                 # configuration for Form Options
                                                      # ...
        initfunctions:                                # configuration for Init Functions
                                                      # ...
        conditions:                                   # configuration for Conditions
                                                      # ...
        postfunctions:                                # configuration for Post Functions
                                                      # ...
```

Frontend Options Configuration
==============================



Attributes Configuration
========================

Action define configuration of attributes. Action can manipulate it's own data (Action Context) that is mapped by
Attributes. Each attribute must to have a type and may have options.

Single attribute can be described with next configuration:

* **unique name**
    Attributes should have unique name in scope of Action that they belong to. Form configuration references attributes
    by this value.
* **type**
    *string*
    Type of attribute. Next types are supported:
    * **boolean**
    * **bool**
        *alias for boolean*
    * **integer**
    * **int**
        *alias for integer*
    * **float**
    * **string**
    * **array**
        Elements of array should be scalars or objects that supports serialize/deserialize
    * **object**
        Object should support serialize/deserialize, option "class" is required for this type
    * **entity**
        Doctrine entity, option "class" is required and it must be a Doctrine manageable class
* **label**
    *string*
    Label can be shown in the UI
* **property_path**
    *string*
    Used to work with attribute value by reference and specifies path to data storage. If property path is specified
    then all other attribute properties except name are optional - they can be automatically guessed
    based on last element (field) of property path.
* **options**
    Options of an attribute. Currently next options are supported
    * **class**
        *string*
        Fully qualified class name. Allowed only when type either entity or object.
    * **multiple**
        *boolean*
        Indicates whether several entities are supported. Allowed only when type is entity.

**Notice**
Attribute configuration does not contain any information about how to render attribute on step forms, it's
responsibility of "Form Options".

Example
-------

```
actions:
    demo_action:
        # ...
        new_account:
            label: 'Account'
            type: entity
            entity_acl:
                delete: false
            options:
                class: OroCRM\Bundle\AccountBundle\Entity\Account
        new_company_name:
            label: 'Company name'
            type: string
        opportunity:
            property_path: sales_funnel.opportunity
        opportunity_name:
            property_path: sales_funnel.opportunity.name
```

Pre Conditions and Conditions Configuration
===========================================
* **preconditions**
    Configuration of Pre Conditions that must satisfy to allow showing action button
* **conditions**
    Configuration of Conditions that must satisfy to allow action

Pre Functions, Init Functions and Post Functions Configuration
==============================================================

* **prefunctions**

* **initfunctions**

* **postfunctions**


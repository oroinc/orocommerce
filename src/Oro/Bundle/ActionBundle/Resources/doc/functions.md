Functions
=========

Table of Contents
-----------------
 - [Call Service Method](#call-service-method)

Call Service Method
-------------------

**Class:** Oro\Bundle\ActionBundle\Action\CallServiceMethod

**Alias:** call_service_method

**Description:** Triggers call method from service with parameters.

**Parameters:**
 - attribute - attribute where method result value should be set (optional)
 - service - service name
 - method - name of method to call
 - method_parameters - list of parameters that will be passed to method call.

**Configuration Example**
```
- @call_service_method:
    conditions:
        # optional condition configuration
    parameters:
        attribute: $.em
        service: doctrine
        method: getManagerForClass
        method_parameters: ['Acme\Bundle\DemoBundle\Entity\User']

OR

- @call_method:
    attribute: $.em
    service: doctrine
    method: getManagerForClass
    method_parameters: ['Acme\Bundle\DemoBundle\Entity\User']
```

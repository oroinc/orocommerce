Upgrade from beta.5
===================

FrontendBundle:
---------------
- Changes in `Oro\Bundle\FrontendBundle\Helper\ActionApplicationsHelper`
    - implements `Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface` without extending from `Oro\Bundle\ActionBundle\Helper\ApplicationsHelper`
    - used traits `Oro\Bundle\ActionBundle\Helper\ApplicationsHelperTrait` and `Oro\Bundle\ActionBundle\Helper\RouteHelperTrait`
    - registered as service `oro_frontend.helper.applications` with configured routes, now it decorate `oro_action.helper.applications`
    
OrderBundle:
------------
- Changes in `Oro\Bundle\OrderBundle\EventListener\TotalCalculateListener`
    - second constructor argument changed to `Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface` instead `Oro\Bundle\ActionBundle\Helper\ApplicationsHelper`
    - used `Oro\Bundle\ActionBundle\Helper\ApplicationsHelper::DEFAULT_APPLICATION`

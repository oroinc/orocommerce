UPGRADE FROM 1.0.0 to 1.1.0
===========================

FlatRateBundle
--------------
- Change name of the bundle to FlatRateShippingBundle

RFPBundle
---------
* The following classes were removed:
    - `Oro\Bundle\RFPBundle\Datagrid\ActionPermissionProvider`
    - `Oro\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository`
* Removed controllers `RequestStatusController`
* The following fields and methods were removed from `Request` entity:
    - methods `setStatus`/`getStatus`
* Added enum fields `customer_status` and `internal_status` to `Oro\Bundle\RFPBundle\Entity\Request` entity
* Following methods were added to `Oro\Bundle\RFPBundle\Entity\Request` entity:
    - `getRequestAdditionalNotes`
    - `addRequestAdditionalNote`
    - `removeRequestAdditionalNote`
* Added new entities:
    - `Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote`
* Removed entities:
    - `Oro\Bundle\RFPBundle\Entity\RequestStatus`
* Removed following classes:
    - `Oro\Bundle\RFPBundle\Form\Type\RequestStatusTranslationType`
    - `Oro\Bundle\RFPBundle\Form\Type\DefaulRequestStatusType`
    - `Oro\Bundle\RFPBundle\Form\Type\RequestStatusSelectType`
    - `Oro\Bundle\RFPBundle\Form\Type\RequestStatusWithDeletedSelectType`
* The methods `setRequestStatusClass` and `postSubmit` was removed from class `Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType`

FrontendBundle
--------------
* Added transition buttons provide for commerce applications:
    - `FrontendStartTransitionButtonProviderExtension`
    - `FrontendTransitionButtonProviderExtension`

Upgrade from beta.3
===================

General
-------
- All code was moved from `OroB2B` namespace to `Oro` namespace

FrontendBundle:
---------------
- Value for parameter `applications` for `Frontend` part of OroCommerce in operation configuration renamed from `frontend` to `commerce`.

CheckoutBundle:
---------------
- Second argument `$checkoutType = null` of method `Oro\Bundle\CheckoutBundle\Controller\Frontend\CheckoutController::checkoutAction` was removed.
- Added ninth argument `WorkflowManager $workflowManager` to constructor of `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout`;
- Protected method `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout::getCheckout` was renamed to `getCheckoutWithWorkflowName`.
- Added second argument to protected method `string $workflowName` to method `Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout::isNewCheckoutEntity`.
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\CheckoutBundle\Entity\BaseCheckout` - not using `WorkflowAwareTrait` more. It means that for entity `Oro\Bundle\CheckoutBundle\Entity\Checkout` these fields removed too. 
- Interface `Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface` no longer implements `Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface`.
- Added new property `string $workflowName` to `Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent` and added related `setter` and `getter`.
- Added argument `CheckoutInterface $checkout` to method `Oro\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener::getWorkflowName`.

AlternativeCheckoutBundle:
--------------------------
- Removed fields `workflowItem` and `workflowStep` from entity `Oro\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout` - not using `WorkflowAwareTrait` more.

WebsiteBundle:
--------------
- Field `localization` removed from entity `Website`.

FrontendLocalizationBundle
--------------------------
- Introduced `FrontendLocalizationBundle` - allow to work with `Oro\Bundle\LocaleBundle\Entity\Localization` in
frontend. Provides possibility to manage current AccountUser localization-settings. Provides Language Switcher for
Frontend.
- Added ACL voter `Oro\Bundle\FrontendLocalizationBundle\Acl\Voter\LocalizationVoter` - prevent removing localizations
that used by default for any WebSite.
- Added `Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager` - for manage current user's
localizations for websites.
- Added `Oro\Bundle\FrontendLocalizationBundle\Extension\CurrentLocalizationExtension` - provide current localization from UserLocalizationManager.

AccountUser
-----------
- Added field `localization` to Entity `AccountUserSettings` - for storing selected `Localization` for websites.
- Field `currency` in Entity `AccountUserSettings` is nullable.

PaymentBundle
-------------
- Added `EventDispatcherInterface` argument to `Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider` constructor.
- Added `getPaymentMethods` method to `Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodsProvider`.
- Added `PaymentTransactionProvider` argument to `Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodsProvider` constructor.
- Added `Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager` for saving payment status for certain entity.
- Added `Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter` for translating payment status labels and getting all available payment statuses.
- Added `Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension` with twig function `get_payment_status_label` which returns translated payment
label.

OrderBundle:
------------
- Moved `get_payment_status_label` twig function to `PaymentBundle` to `Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension`.
- Removed `PaymentStatusProvider` constructor argument from `OroB2B/Bundle/OrderBundle/Twig/OrderExtension`.
- Removed `Oro\Bundle\OrderBundle\Layout\DataProvider\OrderPaymentMethodProvider`.
- Removed method `Oro\Bundle\OrderBundle\Twig\OrderExtension::formatSourceDocument`
- Removed `Oro\Bundle\OrderBundle\Twig\OrderExtension` constructor first argument `Doctrine\Common\Persistence\ManagerRegistry`

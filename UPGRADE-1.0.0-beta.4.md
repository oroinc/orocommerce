Upgrade from beta.3
===================

CheckoutBundle:
---------------
- Second argument of method `OroB2B\Bundle\CheckoutBundle\Controller\Frontend\CheckoutController::checkoutAction` changed from `$id` to `WorkflowItem $workflowItem` and third argument `$checkoutType = null` was removed.
- Added ninth argument `WorkflowManager $workflowManager` to constructor of `OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout`;
- Protected method `OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout::getCheckout` was renamed to `getCheckoutWithWorkflowName`.
- Added second argument to protected method `string $workflowName` to method `OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout::isNewCheckoutEntity`.
- Removed fields `workflowItem` and `workflowStep` from entity `OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout` - not using `WorkflowAwareTrait` more. It means that for entity `OroB2B\Bundle\CheckoutBundle\Entity\Checkout` these fields removed too. 
- Interface `OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface` no longer implements `Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface`.
- Added new property `string $workflowName` to `OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent` and added related `setter` and `getter`.
- Added argument `CheckoutInterface $checkout` to method `OroB2B\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener::getWorkflowName`.

AlternativeCheckoutBundle:
--------------------------
- Removed fields `workflowItem` and `workflowStep` from entity `OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout` - not using `WorkflowAwareTrait` more.

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

AccountUser
-----------
- Added field `localization` to Entity `AccountUserSettings` - for storing selected `Localization` for websites.
- Field `currency` in Entity `AccountUserSettings` is nullable.

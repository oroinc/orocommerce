Upgrade from beta.3
===================

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

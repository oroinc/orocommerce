# OroConsentBundle

OroConsentBundle helps process customers personal data in line with GDPR rules.

OroConsentBundle extends the customer registration, checkout and RFQ submission processes in OroCommerce and provides the ability for storefront customers to accept mandatory and optional consents during registration, RFQ submission, and checkout.

## Overview

This bundle includes the UI for admin users to create and manage the consents and manage their visibility.

The entire functionality of this bundle can be disabled through a feature toggle in the System Configuration UI.

**Notes**: _Please keep in mind that if there are no enabled consents in the system configuration, the consent feature is disabled._

## Examples

* [Add a customer consent field to a custom form](./Resources/doc/add_form_field.md)
* [Add the Agreements step to a custom checkout workflow based on Default Checkout](./Resources/doc/default_checkout.md)
* [Add the Agreements section to a custom checkout workflow based on Single Page Checkout](./Resources/doc/single_page_checkout.md)

## Dependencies

* `Oro\Bundle\EntityBundle`
* `Oro\Bundle\ConfigBundle`
* `Oro\Bundle\FeatureToggleBundle`
* `Oro\Bundle\SecurityBundle`
* `Oro\Bundle\FormBundle`
* `Oro\Bundle\LocaleBundle`
* `Oro\Bundle\CMSBundle`
* `Oro\Bundle\WebCatalogBundle`
* `Oro\Bundle\CustomerBundle`
* `Oro\Bundle\RFPBundle`

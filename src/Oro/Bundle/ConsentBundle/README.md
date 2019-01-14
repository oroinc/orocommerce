# OroConsentBundle

OroConsentBundle helps process customers personal data in line with GDPR rules.

Provides ability to manage various consents (optional and required) to be accepted by the storefront customers during registration, RFQ submission and checkout. Extends the customer registration, checkout and RFQ submission processes in OroCommerce to allow for collection of various user consents.

## Overview

This bundle includes the UI for admin users to create and manage the consents and manage their visibility.

The entire functionality of this bundle can be disabled through a feature toggle in the System Configuration UI.

**Notes**: _Please pay attention that if there is no enabled consents in the system config than consent feature will be disabled._

## Examples

* [Add customer consent field to own form](./Resources/doc/add_form_field.md)
* [Add "Agreements" step to custom checkout workflow based on "Default Checkout"](./Resources/doc/default_checkout.md)
* [Add "Agreements" section to custom checkout workflow based on "Single Page Checkout"](./Resources/doc/single_page_checkout.md)

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

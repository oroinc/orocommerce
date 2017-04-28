OroApruveBundle
===============

Table of Contents
-----------------
 - [Description](#description)
 - [Technical Components](#technical-components)
 - [PaymentTransaction Lifecycle](#paymenttransaction-lifecycle)
 - [Things to Consider](#things-to-consider)
 - [FAQ](#faq)
 - [Links](#links)


Description:
------------

***OroApruveBundle*** provides [Apruve][0] payment method integration, which means the following:
1. Apruve integration type
2. Apruve payment method which can be used in checkout process


Technical Components:
---------------------
1. Integration type:
 - `Oro\Bundle\ApruveBundle\Integration\ApruveChannelType`
 - `Oro\Bundle\ApruveBundle\Integration\ApruveTransport`
2. Payment method:
 - `Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod`
 - see namespace `Oro\Bundle\ApruveBundle\Method\PaymentAction` for concrete payment method actions implementations
3. Apruve-specific models and builders for them:
 - see namespaces `Oro\Bundle\ApruveBundle\Apruve\Model` and `Oro\Bundle\ApruveBundle\Apruve\Builder`
4. Apruve rest client:
 - `Oro\Bundle\ApruveBundle\Client\ApruveRestClient` - works with `RestClientFactoryInterface` under the hood
 - `Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest` - request DTO
5. Apruve webhooks:
 - `Oro\Bundle\ApruveBundle\Controller\WebhookController` - provides route for webhooks
 - `Oro\Bundle\ApruveBundle\EventListener\Callback\PaymentCallbackListener` - handles incoming webhooks. Delegates further processing to concrete payment method actions.


PaymentTransaction Lifecycle:
--------------------------------
1. PaymentTransaction 1 is created in `Oro\Bundle\ApruveBundle\Method\PaymentAction\PurchasePaymentAction` when a customer clicks Submit on the last step of checkout process.

2. If a customer authorises payment in Apruve lightbox, PaymentTransaction 1 is updated with Apruve Order Id and is marked as successful in `Oro\Bundle\ApruveBundle\Method\PaymentAction\AuthorizePaymentAction`. Otherwise - nothing is changed.

3. Once a payment is authorized, it can be invoiced using "Invoice" button in admin area on Order view page in transactions datagrid. When you click "Invoice", PaymentTransaction 2 is getting created along with Apruve Invoice and Shipment entities in `Oro\Bundle\ApruveBundle\Method\PaymentAction\InvoicePaymentAction`.

4. When a customer fulfils invoice, Apruve notifies about it via webhook `invoice.closed` which is processed in `Oro\Bundle\ApruveBundle\Method\PaymentAction\CompletePaymentAction`. PaymentTransaction 3 is created and marked as successful.

5. When a customer declines an invoice or does not pay for more than certain amount of days (90 by default) - Apruve cancels corresponding Order and Invoice, and notifies about this event via webhook `order.canceled` which is processed in `Oro\Bundle\ApruveBundle\Method\PaymentAction\CancelPaymentAction`. PaymentTransaction 3 is created and marked as not successful.



Things to Consider:
-------------------
1. Apruve does not properly respect `price_total_cents` property of Apruve LineItem - it is not included in the secure hash generation on Apruve side, though Apruve takes `amount_cents` (see [Merchant Integration Tutorial][1]). That's why it was decided (and approved by Apruve Support) to use `amount_cents` instead of `price_total_cents` property in Apruve LineItem entity. See `Oro\Bundle\ApruveBundle\Apruve\Model\ApruveLineItem` and `Oro\Bundle\ApruveBundle\Apruve\Generator\OrderSecureHashGenerator` for details.

2. Apruve wants to be notified about shipments from merchants who sell physical goods, but does not for other merchant types. Due to this fact it will not fulfil invoices in cases when it was not notified of shipment (when Shipment entity is not created via API) for merchants of physical goods. In order to unify the behavior for all types of merchants, it was decided to always notify Apruve about shipment, no matter goods of what type were sold. Shipment entity is created in Apruve along with Invoice entity when "Invoice" button is clicked on Order view page in the transactions datagrid.


FAQ:
----
#### Is customer being forwarded to the Apruve-hosted payment page during checkout process?

No, whole checkout process is being done without leaving commerce application. User has to authorise payment in Apruve popup (lightbox) on the last step.

#### How can I login/register in Apruve sandbox?

You have to ask Apruve Support (support@apruve.com) for test merchant and buyer account.

#### I have a corporate Apruve account, but it is not accepted during checkout process

Corporate accounts differ. You should have a corporate account associated exactly with the merchant account you are trying to deal with.


Links:
------
 - [Apruve Sandbox][1]
 - [Apruve Docs][2]
 - [Apruve Guides][3]


[0]: https://www.apruve.com
[1]: https://test.apruve.com
[2]: https://docs.apruve.com/reference
[3]: https://docs.apruve.com/guides
[4]: https://docs.apruve.com/guides/merchant-integration-tutorial#1b-creating-a-secure-hash

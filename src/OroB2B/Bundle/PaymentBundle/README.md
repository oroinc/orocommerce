OroB2B\Bundle\PaymentBundle\OroB2BPaymentBundle
===============================================

Table of Contents
-----------------
 - [Testing the PayPal response](#testing-the-paypal-response)

Testing the PayPal response:
----------------------------

The process of paying with PayPal Payments Pro or Payflow Gateway includes listening for a notify response from PayPal servers. In order to make payments more secure we implemented the IP address filtering which will only accept responses from the white list of PayPal servers addresses. The white list itself is stored in the [`PaymentBundle/EventListener/Callback/PayflowIPCheckListener.php`](https://github.com/laboro/dev/blob/master/package/commerce/src/OroB2B/Bundle/PaymentBundle/EventListener/Callback/PayflowIPCheckListener.php) class.

In order for this IP check to work, the bundle has to be able to resolve the IP address from the request that is coming from PayPal server. This is usually the case in the production environment when you have your server exposed to the internet.

For the purpose of testing the payments on the developer machine you will usually have to use some kind of tunneling service, for example [ngrok.com](https://ngrok.com). The problem with the tunneling services is they tend to put the original request IP address in the header like `X-Forwarded-For` and Symfony doesn't resolve this address as client IP by default.

Luckily there is and option for Symfony to do that by enabling trusted proxies. You can see the detailed explanation in the Symfony documentation [here](http://symfony.com/doc/current/components/http_foundation/trusting_proxies.html) and [here](http://symfony.com/doc/current/cookbook/request/load_balancer_reverse_proxy.html).

To make the configuration process simpler, we provided the stub configuration for this option with the bundle. It is located in the `PaymentBundle/Resources/config/oro/app.yml` and is commented out by default. To enable it, uncomment it like this:

```
#PaymentBundle/Resources/config/oro/app.yml
framework:
    trusted_proxies: [ 127.0.0.1 ]
```

You may also need to change the IP address of the trusted proxy if it's different from `127.0.0.1`. You have to clear the cache to apply the settings in this file.

You can also make this setting in the standard Symfony way - in the `app/config/config_{dev|test}.yml`.

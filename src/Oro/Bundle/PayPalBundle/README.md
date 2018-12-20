# OroPayPalBundle

OroPayPalBundle adds [PayPal](https://www.paypal.com/) integration to the OroCommerce application. For the OroCommerce management console administrator, the bundle provides the ability to enable and configure PayPal payment methods for customer orders. Once PayPal payment methods are enabled, customer users can pay for orders using their existing PayPal account or credit and debit cards.

## Table of Contents

 - [Testing the PayPal response](#testing-the-paypal-response)

## Testing the PayPal response:


The process of paying with PayPal Payments Pro or Payflow Gateway includes listening for a notify response from PayPal servers. In order to make payments more secure we implemented the IP address filtering which will only accept responses from the white list of PayPal servers addresses. The white list itself is stored in the [`EventListener/Callback/PayflowIPCheckListener.php`](EventListener/Callback/PayflowIPCheckListener.php) class.

In order for this IP check to work, the bundle has to be able to resolve the IP address from the request that is coming from PayPal server. This is usually the case in the production environment when you have your server exposed to the internet.

For the purpose of testing the payments on the developer machine you will usually have to use some kind of tunneling service, for example [ngrok.com](https://ngrok.com). The problem with the tunneling services is they tend to put the original request IP address in the header like `X-Forwarded-For` and Symfony doesn't resolve this address as client IP by default.

Luckily there is an option for Symfony to do that by enabling trusted proxies. You can see the detailed explanation in the [Symfony documentation](http://symfony.com/doc/3.4/deployment/proxies.html).

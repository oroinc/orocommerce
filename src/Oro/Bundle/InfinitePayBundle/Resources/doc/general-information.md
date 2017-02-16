#General information.

**Configuration**
 
InfinitePay configuration can be found in:
 
```code
Configuration → Commerce → Payment → Infinite Pay.
```

There are Display Options like bundle activation status and labels for frontend display. The account data (provided by InfinitePay upon registration) need to be filled out under **Credentials**.
In **Advanced Options** Auto Capture and/or Auto Activation is enabled.


**Checkout**

For a successful payment request to InfinitePay the customer must also provide additional information on the payment step.
The following fields will be shown on the payment step if InfinitePay was selected as payment provider: 
- _company_
- _email address_
- _legal form_ (i.e. Freelancer, GbR)


**Payment Finalization**

To set an invoice as paid, there are two approaches.
The first one is auto-detection by InfinitePay. This requires the shop owner to book this option with InfinitePay and making the company bank account accessible to InfinityPay.
The second option is to inform InfinitePay when the money for an order (respective invoice) was received. This is done by triggering an **Apply Transaction** to InfinityPay referencing the order id.

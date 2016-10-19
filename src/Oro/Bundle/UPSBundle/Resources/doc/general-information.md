#General information.

Configuration data of 'Shipping Origin' is used in UPS bundle as shipper and ship from address, that is why it should be configured on 'Configuration' page:

```code
System -> Configuration -> Commerce -> Shipping -> Shipping Origin
```

UPS bundle provides new integration type with specific params (such as Base URL, Shipping Service, etc.) and can be created on 'Integration' page:

```code
System -> Integrations -> Manage Integrations
```

It is possible to create few different integrations with 'UPS' type.
After integration will be saved, a new shipping method will be available in system with shipping types, which was selected as shipping services.
Created shipping method with types will be available on 'Shipping Rules' page: 

```code
System -> Shipping Rules
```

During checkout process on front-end the 'Shipping Method' step will show new UPS method with prices if rule will be triggered.

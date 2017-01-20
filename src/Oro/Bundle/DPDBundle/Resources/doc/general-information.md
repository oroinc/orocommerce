#General information.

The DPD shipping provides functionality to:
* Implement the _DPD classic_ shipping method type.
* Use _flat-rate_ or _table-rate_ shipping cost calculation.
* CSV import/export for _table-rates_.
* API based parcel pick-up day/time calculation and validation. 
* API based shipping address validation.
* API based PDF label retrieval for orders.

Configuration data of _Shipping Origin_ is used in DPD bundle as the parcel pick-up address. This address will be used to calculate the locale specific available pick-up days. It should be configured in its 'Configuration' page:

```code
System -> Configuration -> Commerce -> Shipping -> Shipping Origin
```

DPD bundle provides a new integration type with specific parameters and can be created in 'Integration' page:
Parameters overview:
* Live Mode - If set, the API will use the production environment configuration.
* Cloud User Id - Identification of the DPD customer. Assigned by DPD.
* Cloud User Token - Customer password of the DPD customer. Assigned by DPD.
* Shipping Service - Shipping service type, currently only _classic_ type is supported.
* Rate policy - Defines the rate type to be used in price calculation.
* Flat Rate Price - Monetary amount that should be charged for shipping using _flat-rate_.
* Rates CSV - Upload/Download CSV file with _table-rates_. The entry that more specifically matches the address will be the one used (e.g. an entry with _country_ and _region_ that matches a shipping address will have precedence over another entry with just _country_).  
* Label Size - Options for the different label size types.
* Label Position - Options for the different label printing positions.

```code
System -> Integrations -> Manage Integrations
```

It is possible to create different integrations with the 'DPD' type.
After an integration is saved, a new shipping method will become available to use in _Shipping Rules_. 

```code
System -> Shipping Rules
```

During checkout process on front-end, the 'Shipping Method' step will show a new DPD method with price if a shipping rule is triggered.

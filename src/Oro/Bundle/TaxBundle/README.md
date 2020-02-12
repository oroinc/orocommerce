# OroTaxBundle

OroTaxBundle introduces tax-related features in the OroCommerce application.

The bundle enables management console users to create taxes, configure tax types for products, customers, and jurisdictions, as well as setup tax application rules based on the tax types.

With the corresponding configuration of the bundle, customers can view applied taxes for orders and quotes.

The bundle also provides an interface that enables developers to implement integrations with additional third-party tax providers to the OroCommerce applications.

## Table of Contents

 - [Create Custom Tax Provider](#create-custom-tax-provider)
 
## Create Custom Tax Provider

You can add your own custom tax logic with custom tax provider.

1. Create tax provider that implements [TaxProviderInterface](./Provider/TaxProviderInterface) interface:

```php
<?php

namespace Acme\Bundle\DemoBundle\Provider;

use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;

class DemoTaxProvider implements TaxProviderInterface
{
    const LABEL = 'acme.demo.providers.demo.label';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return self::LABEL;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function loadTax($object)
    {
        // implement your loadTax() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getTax($object)
    {
        // implement your getTax() method.
    }

    /**
     * {@inheritdoc}
     */
    public function saveTax($object)
    {
        // implement your saveTax() method.
    }

    /**
     * {@inheritdoc}
     */
    public function removeTax($object)
    {
        // implement your removeTax() method.
    }
}
```

2. Register your own tax provider in the service container using **oro_tax.tax_provider** tag with **alias** attribute
   that contains unique name of a tax provider:

```yml
# src/Acme/Bundle/DemoBundle/Resources/config/services.yml

services:
    acme_demo.tax_provider.demo:
        class: 'Acme\Bundle\DemoBundle\Provider\DemoTaxProvider'
        tags:
            - { name: oro_tax.tax_provider, alias: demo, priority: 10 }

```

3. Go to admin panel **System/Configuration/Taxation/Tax Calculation** and chose your own **Tax Provider** in the choice list.

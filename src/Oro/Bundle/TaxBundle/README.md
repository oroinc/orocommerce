# OroTaxBundle

OroTaxBundle introduces tax-related features in the OroCommerce application.

The bundle enables management console users to create taxes, configure tax types for products, customers, and jurisdictions, as well as setup tax application rules based on the tax types.

With the corresponding configuration of the bundle, customers can view applied taxes for orders and quotes.

The bundle also provides an interface that enables developers to implement integrations with additional third-party tax providers to the OroCommerce applications.

## Table of Contents
 - [Create Custom Tax Provider](#create-custom-tax-provider)
 
## Create Custom Tax Provider

You can add your own custom tax logic with custom tax provider.
Create tax provider that implements `Oro\Bundle\TaxBundle\Provider\TaxProviderInterface` interface.

```php
<?php
// src/Acme/Bundle/DemoBundle/Provider/DemoTaxProvider.php

namespace Acme\Bundle\DemoBundle\Provider;

use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;

class DemoTaxProvider implements TaxProviderInterface
{
    const NAME = 'demo';
    const LABEL = 'acme.demo.providers.demo.label';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

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
    public function createTaxValue($object)
    {
        // implement your createTaxValue() method.
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

Register your own tax provider using **oro_tax.tax_provider** tag

```yml
# src/Acme/Bundle/DemoBundle/Resources/config/services.yml

services:
    acme_demo.tax_provider.demo:
        class: 'Acme\Bundle\DemoBundle\Provider\DemoTaxProvider'
        public: false
        tags:
            - { name: oro_tax.tax_provider, priority: 10 }

```

Go to admin panel **System/Configuration/Taxation/Tax Calculation** and chose your own **Tax Provider** in the choice list.

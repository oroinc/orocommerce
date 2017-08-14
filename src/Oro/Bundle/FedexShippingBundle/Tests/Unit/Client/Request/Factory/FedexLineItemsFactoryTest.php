<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\Request\Factory;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexLineItemsFactory;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use PHPUnit\Framework\TestCase;

class FedexLineItemsFactoryTest extends TestCase
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var MeasureUnitConversion
     */
    private $measureUnitConverter;

    /**
     * @var FedexLineItemsFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->measureUnitConverter = $this->createMock(MeasureUnitConversion::class);


    }
}

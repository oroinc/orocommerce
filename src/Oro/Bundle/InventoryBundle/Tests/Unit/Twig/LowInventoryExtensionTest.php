<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Twig;

use Oro\Bundle\InventoryBundle\Tests\Unit\Stubs\LowInventoryProviderStub;
use Oro\Bundle\InventoryBundle\Twig\LowInventoryExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class LowInventoryExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait, EntityTrait;

    /**
     * @var LowInventoryExtension
     */
    protected $lowInventoryExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $container = self::getContainerBuilder()
            ->add('oro_inventory.inventory.low_inventory_provider', new LowInventoryProviderStub())
            ->getContainer($this);

        $this->lowInventoryExtension = new LowInventoryExtension($container);
    }

    public function testIsLowInventoryTrue()
    {
        $resultIsLowInventory = self::callTwigFunction(
            $this->lowInventoryExtension,
            'oro_is_low_inventory_product',
            [
                $this->getEntity(
                    Product::class,
                    ['id' => LowInventoryProviderStub::PRODUCT_ID_WITH_ENABLED_LOW_INVENTORY]
                )
            ]
        );

        $this->assertTrue($resultIsLowInventory);
    }

    public function testIsLowInventoryFalse()
    {
        $resultIsLowInventory = self::callTwigFunction(
            $this->lowInventoryExtension,
            'oro_is_low_inventory_product',
            [
                $this->getEntity(
                    Product::class,
                    ['id' => LowInventoryProviderStub::PRODUCT_ID_WITH_DISABLED_LOW_INVENTORY]
                )
            ]
        );

        $this->assertFalse($resultIsLowInventory);
    }
}

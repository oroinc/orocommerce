<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\FieldsOptionsProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldsOptionsProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;
    private ManagerRegistry|MockObject $managerRegistry;
    private FieldsOptionsProvider $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->provider = new FieldsOptionsProvider($this->configManager, $this->managerRegistry);
    }

    public function testGetAvailableFieldsForGroupingFormOptions()
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->willReturn('Oro\Bundle\ProductBundle\Entity\Product');

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->configManager->expects($this->exactly(2))
            ->method('getEntityConfig')
            ->will($this->returnValueMap([
                [
                    'entity',
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    $this->createConfig('oro_product.entity.label')
                ],
                [
                    'entity',
                    'Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem',
                    $this->createConfig('oro_checkout.line_item.label')
                ]
            ]));

        $this->configManager->expects($this->exactly(5))
            ->method('getFieldConfig')
            ->will($this->returnValueMap([
                [
                    'entity',
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'id',
                    $this->createConfig('oro_product.product.id.label')
                ],
                [
                    'entity',
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'owner',
                    $this->createConfig('oro_product.product.owner.label')
                ],
                [
                    'entity',
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'category',
                    $this->createConfig('oro_product.product.category.label')
                ],
                [
                    'entity',
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'brand',
                    $this->createConfig('oro_product.product.brand.label')
                ],
                [
                    'entity',
                    'Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem',
                    'parentProduct',
                    $this->createConfig('oro_checkout.checkout_line_item.parent_product.label')
                ],
            ]));

        $expected = [
            'oro_product.entity.label' => [
                'oro_product.product.id.label' => 'product.id',
                'oro_product.product.owner.label' => 'product.owner',
                'oro_product.product.category.label' => 'product.category',
                'oro_product.product.brand.label' => 'product.brand'
            ],
            'oro_checkout.line_item.label' => [
                'oro_checkout.checkout_line_item.parent_product.label' => 'parentProduct'
            ]
        ];

        $result = $this->provider->getAvailableFieldsForGroupingFormOptions();
        $this->assertEquals($expected, $result);
    }

    private function createConfig(string $label)
    {
        return new Config(new EntityConfigId('entity'), ['label' => $label]);
    }
}

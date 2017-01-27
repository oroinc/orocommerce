<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class AbstractProductDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    public function testBuildEmptyData()
    {
        $this->assertFalse($this->extension->isAddItemCalled());

        $data = [ProductDataStorage::ENTITY_DATA_KEY => []];

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertFalse($this->extension->isAddItemCalled());
    }

    public function testBuild()
    {
        $this->assertFalse($this->extension->isAddItemCalled());
        $this->entity->product = null;
        $this->entity->scalar = null;
        $this->entity->assignedUsers = null;
        $this->entity->assignedCustomerUsers = null;

        $sku = 'TEST';
        $product = $this->getProductEntity($sku);
        $assignedUsers = [2, 4, 8];
        $assignedCustomerUsers = [3, 6];
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => [
                'product' => 1,
                'scalar' => 1,
                'assignedUsers' => $assignedUsers,
                'assignedCustomerUsers' => $assignedCustomerUsers,
            ],
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => 3,
                ],
            ],
        ];

        $this->assertMetadataCalled([
            'product' => ['targetClass' => 'Oro\Bundle\ProductBundle\Entity\Product'],
            'assignedUsers' => ['targetClass' => 'Oro\Bundle\UserBundle\Entity\User'],
            'assignedCustomerUsers' => ['targetClass' => 'Oro\Bundle\CustomerBundle\Entity\CustomerUser'],
        ]);
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertTrue($this->extension->isAddItemCalled());

        $this->assertInstanceOf('Oro\Bundle\ProductBundle\Entity\Product', $this->entity->product);
        $this->assertEquals(1, $this->entity->product->getId());
        $this->assertEquals(1, $this->entity->scalar);
        $this->assertCount(3, $this->entity->assignedUsers);
        $this->assertCount(2, $this->entity->assignedCustomerUsers);

        foreach ($this->entity->assignedUsers as $assignedUser) {
            $this->assertInstanceOf('Oro\Bundle\UserBundle\Entity\User', $assignedUser);
            $this->assertContains($assignedUser->getId(), $assignedUsers);
            unset($assignedUsers[array_search($assignedUser->getId(), $assignedUsers)]);
        }

        foreach ($this->entity->assignedCustomerUsers as $assignedCustomerUser) {
            $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\CustomerUser', $assignedCustomerUser);
            $this->assertContains($assignedCustomerUser->getId(), $assignedCustomerUsers);
            unset($assignedCustomerUsers[array_search($assignedCustomerUser->getId(), $assignedCustomerUsers)]);
        }
    }
}

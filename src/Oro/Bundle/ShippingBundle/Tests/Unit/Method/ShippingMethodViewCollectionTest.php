<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
* @SuppressWarnings(PHPMD.TooManyMethods)
* @SuppressWarnings(PHPMD.TooManyPublicMethods)
* @SuppressWarnings(PHPMD.ExcessivePublicCount)
*/
class ShippingMethodViewCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return ShippingMethodViewCollection
     */
    private function createCollection()
    {
        return new ShippingMethodViewCollection();
    }

    public function testAddAndGetMethodView()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';

        $view = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $addResult = $collection->addMethodView($methodId, $view);

        $actualView = $collection->getMethodView($methodId);

        $this->assertEquals($collection, $addResult);
        $this->assertNotNull($actualView);
        $this->assertEquals($view, $actualView);
    }

    public function testGetMethodViewWhenNotExists()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';

        $actualView = $collection->getMethodView($methodId);

        $this->assertNull($actualView);
    }

    public function testAddMethodViewWhenAlreadyExists()
    {
        $methodId = 'someMethodId';

        $collection = $this->createCollection();

        $view = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId, $view);

        $view2 = [
            'someField3' => 'someValue4',
            'someField4' => 'someValue4',
            'sortOrder' => 1
        ];

        $addMethodViewResult = $collection->addMethodView($methodId, $view2);

        $actualView = $collection->getMethodView($methodId);

        $this->assertNotNull($actualView);
        $this->assertEquals($view, $actualView);
        $this->assertEquals($collection, $addMethodViewResult);
    }

    public function testHasMethodView()
    {
        $methodId = 'someMethodId';
        $collection = $this->createCollection();

        $collection->addMethodView($methodId, []);

        $this->assertTrue($collection->hasMethodView($methodId));
    }

    public function testHasMethodViewNotExists()
    {
        $collection = $this->createCollection();

        $this->assertFalse($collection->hasMethodView('someMethodId'));
    }

    public function testRemoveMethodView()
    {
        $methodId = 'someMethodId';
        $collection = $this->createCollection();

        $collection->addMethodView($methodId, []);

        $this->assertTrue($collection->hasMethodView($methodId));

        $removeResult = $collection->removeMethodView($methodId);

        $this->assertEquals($collection, $removeResult);
        $this->assertFalse($collection->hasMethodView($methodId));
    }

    public function testRemoveMethodViewWhenNotExists()
    {
        $methodId = 'someMethodId';
        $collection = $this->createCollection();

        $removeResult = $collection->removeMethodView($methodId);

        $this->assertEquals($collection, $removeResult);
        $this->assertFalse($collection->hasMethodView($methodId));
    }

    public function testAddAndGetMethodTypeView()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId, $methodView);

        $methodTypeView = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $addMethodTypeViewResult = $collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView);

        $actualMethodTypeView = $collection->getMethodTypeView($methodId, $methodTypeId);

        $this->assertEquals($collection, $addMethodTypeViewResult);
        $this->assertEquals($methodTypeView, $actualMethodTypeView);
    }

    public function testGetMethodTypeViewWhenNotExists()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $collection->addMethodView($methodId, []);

        $actualMethodTypeView = $collection->getMethodTypeView($methodId, $methodTypeId);

        $this->assertNull($actualMethodTypeView);
    }

    public function testGetMethodTypeViewWhenMethodTypeNotExists()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $actualMethodTypeView = $collection->getMethodTypeView($methodId, $methodTypeId);

        $this->assertNull($actualMethodTypeView);
    }

    public function testAddMethodTypeViewWhenAlreadyExists()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId, $methodView);

        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView2);

        $actualMethodTypeView = $collection->getMethodTypeView($methodId, $methodTypeId);
        $this->assertEquals($methodTypeView1, $actualMethodTypeView);
    }

    public function testAddMethodTypesViews()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';

        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';

        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeId2 = 'someOtherMethodTypeId';

        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId2, $methodTypeView2);

        $this->assertEquals($methodTypeView1, $collection->getMethodTypeView($methodId, $methodTypeId));
        $this->assertEquals($methodTypeView2, $collection->getMethodTypeView($methodId, $methodTypeId2));
    }

    public function testHasMethodTypeView()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $collection->addMethodView($methodId, []);
        $collection->addMethodTypeView($methodId, $methodTypeId, []);

        $this->assertTrue($collection->hasMethodTypeView($methodId, $methodTypeId));
    }

    public function testHasMethodTypeViewWhenNotExists()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $collection->addMethodView($methodId, []);

        $this->assertFalse($collection->hasMethodTypeView($methodId, $methodTypeId));
    }

    public function testHasMethodTypeViewWhenMethodNotExists()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $this->assertFalse($collection->hasMethodTypeView($methodId, $methodTypeId));
    }

    public function testRemoveMethodTypeView()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $collection->addMethodView($methodId, []);
        $collection->addMethodTypeView($methodId, $methodTypeId, []);

        $this->assertTrue($collection->hasMethodTypeView($methodId, $methodTypeId));

        $removeResult = $collection->removeMethodTypeView($methodId, $methodTypeId);

        $this->assertEquals($collection, $removeResult);
        $this->assertNull($collection->getMethodTypeView($methodId, $methodTypeId));
    }

    public function testRemoveMethodTypeViewWhenNotExists()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $collection->addMethodView($methodId, []);

        $this->assertFalse($collection->hasMethodTypeView($methodId, $methodTypeId));

        $removeResult = $collection->removeMethodTypeView($methodId, $methodTypeId);

        $this->assertEquals($collection, $removeResult);
        $this->assertNull($collection->getMethodTypeView($methodId, $methodTypeId));
    }

    public function testRemoveMethodTypeViewWhenMethodNotExists()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $this->assertFalse($collection->hasMethodTypeView($methodId, $methodTypeId));

        $removeResult = $collection->removeMethodTypeView($methodId, $methodTypeId);

        $this->assertEquals($collection, $removeResult);
        $this->assertNull($collection->getMethodTypeView($methodId, $methodTypeId));
    }

    public function testGetAllMethodsViews()
    {
        $collection = $this->createCollection();

        $this->assertEquals([], $collection->getAllMethodsViews());

        $methodId = 'someMethodId';

        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';

        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeId2 = 'someMethodTypeId2';

        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId2, $methodTypeView2);

        $this->assertEquals([$methodId => $methodView], $collection->getAllMethodsViews());

        $methodId2 = 'someOtherMethodId';

        $methodView2 = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId2, $methodView2);

        $methodTypeId3 = 'someMethodTypeId3';

        $methodTypeView3 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $collection->addMethodTypeView($methodId2, $methodTypeId3, $methodTypeView3);

        $methodTypeId4 = 'someMethodTypeId4';

        $methodTypeView4 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];

        $collection->addMethodTypeView($methodId2, $methodTypeId4, $methodTypeView4);

        $this->assertEquals([$methodId => $methodView, $methodId2 => $methodView2], $collection->getAllMethodsViews());
    }

    public function testGetAllMethodsTypesViews()
    {
        $collection = $this->createCollection();

        $this->assertEquals([], $collection->getAllMethodsViews());

        $methodId = 'someMethodId';

        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';

        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeId2 = 'someMethodTypeId2';

        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId2, $methodTypeView2);

        $this->assertEquals(
            [
                $methodId => [
                    $methodTypeId => $methodTypeView1,
                    $methodTypeId2 => $methodTypeView2,
                ],
            ],
            $collection->getAllMethodsTypesViews()
        );

        $methodId2 = 'someOtherMethodId';

        $methodView2 = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId2, $methodView2);

        $methodTypeId3 = 'someMethodTypeId3';

        $methodTypeView3 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $collection->addMethodTypeView($methodId2, $methodTypeId3, $methodTypeView3);

        $methodTypeId4 = 'someMethodTypeId4';

        $methodTypeView4 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];

        $collection->addMethodTypeView($methodId2, $methodTypeId4, $methodTypeView4);

        $this->assertEquals(
            [
                $methodId => [
                    $methodTypeId => $methodTypeView1,
                    $methodTypeId2 => $methodTypeView2,
                ],
                $methodId2 => [
                    $methodTypeId3 => $methodTypeView3,
                    $methodTypeId4 => $methodTypeView4,
                ],
            ],
            $collection->getAllMethodsTypesViews()
        );

        $collection->clear();

        $this->assertEquals([], $collection->getAllMethodsTypesViews());
        $this->assertEquals([], $collection->getAllMethodsViews());
    }

    public function testToArray()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';

        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';

        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeId2 = 'someMethodTypeId2';

        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];

        $collection->addMethodTypeView($methodId, $methodTypeId2, $methodTypeView2);

        $methodId2 = 'someOtherMethodId';

        $methodView2 = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];

        $collection->addMethodView($methodId2, $methodView2);

        $methodTypeId3 = 'someMethodTypeId3';

        $methodTypeView3 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $collection->addMethodTypeView($methodId2, $methodTypeId3, $methodTypeView3);

        $methodTypeId4 = 'someMethodTypeId4';

        $methodTypeView4 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];

        $collection->addMethodTypeView($methodId2, $methodTypeId4, $methodTypeView4);

        $methodView[ShippingMethodViewCollection::TYPES_FIELD] = [
            $methodTypeId => $methodTypeView1,
            $methodTypeId2 => $methodTypeView2,
        ];
        $methodView2[ShippingMethodViewCollection::TYPES_FIELD] = [
            $methodTypeId3 => $methodTypeView3,
            $methodTypeId4 => $methodTypeView4,
        ];

        $this->assertEquals(
            [
                $methodId => $methodView,
                $methodId2 => $methodView2
            ],
            $collection->toArray()
        );
    }

    public function testIsEmpty()
    {
        $collection = $this->createCollection();

        $methodId = 'someMethodId';

        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $methodTypeView = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];

        $collection->addMethodView($methodId, $methodView);

        $this->assertTrue($collection->isEmpty());

        $collection->addMethodTypeView($methodId, 'someMethodTypeId', $methodTypeView);

        $this->assertFalse($collection->isEmpty());

        $collection->clear();

        $this->assertTrue($collection->isEmpty());
    }
}

<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
* @SuppressWarnings(PHPMD.TooManyPublicMethods)
*/
class ShippingMethodViewCollectionTest extends \PHPUnit\Framework\TestCase
{
    private ShippingMethodViewCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new ShippingMethodViewCollection();
    }

    public function testAddAndGetMethodView()
    {
        $methodId = 'someMethodId';
        $view = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $addResult = $this->collection->addMethodView($methodId, $view);

        $actualView = $this->collection->getMethodView($methodId);

        $this->assertEquals($this->collection, $addResult);
        $this->assertNotNull($actualView);
        $this->assertEquals($view, $actualView);
    }

    public function testGetMethodViewWhenNotExists()
    {
        $this->assertNull($this->collection->getMethodView('someMethodId'));
    }

    public function testAddMethodViewWhenAlreadyExists()
    {
        $methodId = 'someMethodId';

        $view = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId, $view);

        $view2 = [
            'someField3' => 'someValue4',
            'someField4' => 'someValue4',
            'sortOrder' => 1
        ];
        $addMethodViewResult = $this->collection->addMethodView($methodId, $view2);

        $actualView = $this->collection->getMethodView($methodId);

        $this->assertNotNull($actualView);
        $this->assertEquals($view, $actualView);
        $this->assertEquals($this->collection, $addMethodViewResult);
    }

    public function testHasMethodView()
    {
        $methodId = 'someMethodId';

        $this->collection->addMethodView($methodId, []);

        $this->assertTrue($this->collection->hasMethodView($methodId));
    }

    public function testHasMethodViewNotExists()
    {
        $this->assertFalse($this->collection->hasMethodView('someMethodId'));
    }

    public function testRemoveMethodView()
    {
        $methodId = 'someMethodId';

        $this->collection->addMethodView($methodId, []);
        $this->assertTrue($this->collection->hasMethodView($methodId));

        $removeResult = $this->collection->removeMethodView($methodId);
        $this->assertEquals($this->collection, $removeResult);
        $this->assertFalse($this->collection->hasMethodView($methodId));
    }

    public function testRemoveMethodViewWhenNotExists()
    {
        $methodId = 'someMethodId';

        $removeResult = $this->collection->removeMethodView($methodId);
        $this->assertEquals($this->collection, $removeResult);
        $this->assertFalse($this->collection->hasMethodView($methodId));
    }

    public function testAddAndGetMethodTypeView()
    {
        $methodId = 'someMethodId';
        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';
        $methodTypeView = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];
        $addMethodTypeViewResult = $this->collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView);

        $actualMethodTypeView = $this->collection->getMethodTypeView($methodId, $methodTypeId);

        $this->assertEquals($this->collection, $addMethodTypeViewResult);
        $this->assertEquals($methodTypeView, $actualMethodTypeView);
    }

    public function testGetMethodTypeViewWhenNotExists()
    {
        $methodId = 'someMethodId';

        $this->collection->addMethodView($methodId, []);

        $actualMethodTypeView = $this->collection->getMethodTypeView($methodId, 'someMethodTypeId');

        $this->assertNull($actualMethodTypeView);
    }

    public function testGetMethodTypeViewWhenMethodTypeNotExists()
    {
        $this->assertNull($this->collection->getMethodTypeView('someMethodId', 'someMethodTypeId'));
    }

    public function testAddMethodTypeViewWhenAlreadyExists()
    {
        $methodId = 'someMethodId';
        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';
        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView2);

        $actualMethodTypeView = $this->collection->getMethodTypeView($methodId, $methodTypeId);
        $this->assertEquals($methodTypeView1, $actualMethodTypeView);
    }

    public function testAddMethodTypesViews()
    {
        $methodId = 'someMethodId';
        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';
        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeId2 = 'someOtherMethodTypeId';
        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId2, $methodTypeView2);

        $this->assertEquals($methodTypeView1, $this->collection->getMethodTypeView($methodId, $methodTypeId));
        $this->assertEquals($methodTypeView2, $this->collection->getMethodTypeView($methodId, $methodTypeId2));
    }

    public function testHasMethodTypeView()
    {
        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $this->collection->addMethodView($methodId, []);
        $this->collection->addMethodTypeView($methodId, $methodTypeId, []);

        $this->assertTrue($this->collection->hasMethodTypeView($methodId, $methodTypeId));
    }

    public function testHasMethodTypeViewWhenNotExists()
    {
        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $this->collection->addMethodView($methodId, []);

        $this->assertFalse($this->collection->hasMethodTypeView($methodId, $methodTypeId));
    }

    public function testHasMethodTypeViewWhenMethodNotExists()
    {
        $this->assertFalse($this->collection->hasMethodTypeView('someMethodId', 'someMethodTypeId'));
    }

    public function testRemoveMethodTypeView()
    {
        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $this->collection->addMethodView($methodId, []);
        $this->collection->addMethodTypeView($methodId, $methodTypeId, []);
        $this->assertTrue($this->collection->hasMethodTypeView($methodId, $methodTypeId));

        $removeResult = $this->collection->removeMethodTypeView($methodId, $methodTypeId);
        $this->assertEquals($this->collection, $removeResult);
        $this->assertNull($this->collection->getMethodTypeView($methodId, $methodTypeId));
    }

    public function testRemoveMethodTypeViewWhenNotExists()
    {
        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $this->collection->addMethodView($methodId, []);
        $this->assertFalse($this->collection->hasMethodTypeView($methodId, $methodTypeId));

        $removeResult = $this->collection->removeMethodTypeView($methodId, $methodTypeId);
        $this->assertEquals($this->collection, $removeResult);
        $this->assertNull($this->collection->getMethodTypeView($methodId, $methodTypeId));
    }

    public function testRemoveMethodTypeViewWhenMethodNotExists()
    {
        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';

        $this->assertFalse($this->collection->hasMethodTypeView($methodId, $methodTypeId));

        $removeResult = $this->collection->removeMethodTypeView($methodId, $methodTypeId);
        $this->assertEquals($this->collection, $removeResult);
        $this->assertNull($this->collection->getMethodTypeView($methodId, $methodTypeId));
    }

    public function testGetAllMethodsViews()
    {
        $this->assertEquals([], $this->collection->getAllMethodsViews());

        $methodId = 'someMethodId';
        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';
        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeId2 = 'someMethodTypeId2';
        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId2, $methodTypeView2);

        $this->assertEquals([$methodId => $methodView], $this->collection->getAllMethodsViews());

        $methodId2 = 'someOtherMethodId';
        $methodView2 = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId2, $methodView2);

        $methodTypeId3 = 'someMethodTypeId3';
        $methodTypeView3 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];
        $this->collection->addMethodTypeView($methodId2, $methodTypeId3, $methodTypeView3);

        $methodTypeId4 = 'someMethodTypeId4';
        $methodTypeView4 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];
        $this->collection->addMethodTypeView($methodId2, $methodTypeId4, $methodTypeView4);

        $this->assertEquals(
            [$methodId => $methodView, $methodId2 => $methodView2],
            $this->collection->getAllMethodsViews()
        );
    }

    public function testGetAllMethodsTypesViews()
    {
        $this->assertEquals([], $this->collection->getAllMethodsViews());

        $methodId = 'someMethodId';
        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';
        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeId2 = 'someMethodTypeId2';
        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId2, $methodTypeView2);

        $this->assertEquals(
            [
                $methodId => [
                    $methodTypeId => $methodTypeView1,
                    $methodTypeId2 => $methodTypeView2,
                ],
            ],
            $this->collection->getAllMethodsTypesViews()
        );

        $methodId2 = 'someOtherMethodId';
        $methodView2 = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId2, $methodView2);

        $methodTypeId3 = 'someMethodTypeId3';
        $methodTypeView3 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];
        $this->collection->addMethodTypeView($methodId2, $methodTypeId3, $methodTypeView3);

        $methodTypeId4 = 'someMethodTypeId4';
        $methodTypeView4 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];
        $this->collection->addMethodTypeView($methodId2, $methodTypeId4, $methodTypeView4);

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
            $this->collection->getAllMethodsTypesViews()
        );

        $this->collection->clear();
        $this->assertEquals([], $this->collection->getAllMethodsTypesViews());
        $this->assertEquals([], $this->collection->getAllMethodsViews());
    }

    public function testToArray()
    {
        $methodId = 'someMethodId';
        $methodView = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId, $methodView);

        $methodTypeId = 'someMethodTypeId';
        $methodTypeView1 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView1);

        $methodTypeId2 = 'someMethodTypeId2';
        $methodTypeView2 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];
        $this->collection->addMethodTypeView($methodId, $methodTypeId2, $methodTypeView2);

        $methodId2 = 'someOtherMethodId';
        $methodView2 = [
            'someField1' => 'someValue1',
            'someField2' => 'someValue2',
            'sortOrder' => 1
        ];
        $this->collection->addMethodView($methodId2, $methodView2);

        $methodTypeId3 = 'someMethodTypeId3';
        $methodTypeView3 = [
            'someTypeField1' => 'someTypeValue1',
            'someTypeField2' => 'someTypeValue2',
        ];
        $this->collection->addMethodTypeView($methodId2, $methodTypeId3, $methodTypeView3);

        $methodTypeId4 = 'someMethodTypeId4';
        $methodTypeView4 = [
            'someTypeField3' => 'someTypeValue3',
            'someTypeField4' => 'someTypeValue4',
        ];
        $this->collection->addMethodTypeView($methodId2, $methodTypeId4, $methodTypeView4);

        $methodView['types'] = [$methodTypeId => $methodTypeView1, $methodTypeId2 => $methodTypeView2];
        $methodView2['types'] = [$methodTypeId3 => $methodTypeView3, $methodTypeId4 => $methodTypeView4];

        $this->assertEquals(
            [$methodId => $methodView, $methodId2 => $methodView2],
            $this->collection->toArray()
        );
    }

    public function testIsEmpty()
    {
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

        $this->collection->addMethodView($methodId, $methodView);
        $this->assertTrue($this->collection->isEmpty());

        $this->collection->addMethodTypeView($methodId, 'someMethodTypeId', $methodTypeView);
        $this->assertFalse($this->collection->isEmpty());

        $this->collection->clear();
        $this->assertTrue($this->collection->isEmpty());
    }
}

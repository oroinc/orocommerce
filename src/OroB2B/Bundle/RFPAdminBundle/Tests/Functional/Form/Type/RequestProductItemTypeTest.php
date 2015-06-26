<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Functional\Form\Type;

use Symfony\Component\Form\FormEvent;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemType;
use OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestProductItemTypeTest extends WebTestCase
{
    /**
     * @var RequestProductItemType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->formType = new RequestProductItemType(static::getContainer()->get('translator'));

        $this->loadFixtures([
            'OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures\LoadRequestData',
        ]);
    }

    /**
     * @param \Closure $inputDataCallback
     * @param \Closure $expectedDataCallback
     *
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData($inputDataCallback, $expectedDataCallback)
    {
        $inputData      = $inputDataCallback();
        $expectedData   = $expectedDataCallback();

        $form = static::getContainer()->get('form.factory')->create($this->formType, null, []);

        $this->formType->preSetData(new FormEvent($form, $inputData));

        $this->assertTrue($form->has('productUnit'));

        $options = $form->get('productUnit')->getConfig()->getOptions();

        foreach ($expectedData as $key => $value) {
            $this->assertEquals($value, $options[$key], $key);
        }
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        return [
            'choices is []' => [
                'inputData'     => function () {
                    return null;
                },
                'expectedData'  => function () {
                    return [
                        'choices'       => [],
                        'empty_value'   => null,
                    ];
                },
            ],
            'choices is ProductUnit[]' => [
                'inputData'     => function () {
                    return $this->getRequestProductItem(LoadRequestData::REQUEST1);
                },
                'expectedData'  => function () {
                    $requestProductItem = $this->getRequestProductItem(LoadRequestData::REQUEST1);
                    return [
                        'choices'       => $this->getUnits($requestProductItem->getRequestProduct()->getProduct()),
                        'empty_value'   => null,
                    ];
                },
            ],
            'choices is [] and unit is deleted' => [
                'inputData'     => function () {
                    /* @var $requestProductItem RequestProductItem */
                    $requestProductItem = $this->getRequestProductItem(LoadRequestData::REQUEST1);

                    $requestProductItem->getRequestProduct()->getProduct()->getUnitPrecisions()->clear();

                    return $requestProductItem;
                },
                'expectedData'  => function () {
                    $requestProductItem = $this->getRequestProductItem(LoadRequestData::REQUEST1);
                    return [
                        'choices'       => [],
                        'empty_value'   => $this->trans(
                            'orob2b.rfpadmin.message.requestproductitem.unit.removed',
                            [
                                '{title}' => $requestProductItem->getProductUnitCode(),
                            ]
                        ),
                    ];
                },
            ],
        ];
    }

    /**
     * @param string $id
     * @return RequestProductItem
     */
    protected function getRequestProductItem($id)
    {
        /* @var $request Request */
        $request = $this->getReference($id);

        /* @var $requestProduct RequestProduct */
        $requestProduct = $request->getRequestProducts()->first();

        $this->assertInstanceOf('OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct', $requestProduct);

        /* @var $item0 RequestProductItem */
        $item0 = $requestProduct->getRequestProductItems()->first();

        $this->assertInstanceOf('OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem', $item0);

        return $item0;
    }

    /**
     * @param Product $item
     * @return array|ProductUnit[]
     */
    protected function getUnits(Product $item)
    {
        $units = [];
        foreach ($item->getUnitPrecisions() as $precision) {
            /* @var $precision ProductUnitPrecision */
            $units[] = $precision->getUnit();
        }

        return $units;
    }

    /**
     * @param string $id
     * @param array $parameters
     * @return string
     */
    protected function trans($id, array $parameters = [])
    {
        return static::getContainer()->get('translator')->trans($id, $parameters);
    }
}

<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Functional\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductType;
use OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestProductTypeTest extends WebTestCase
{
    /**
     * @var RequestProductType
     */
    protected $formType;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->formType = new RequestProductType(static::getContainer()->get('translator'));

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
    public function testPreSetData(\Closure $inputDataCallback, \Closure $expectedDataCallback)
    {
        $inputData      = $inputDataCallback();
        $expectedData   = $expectedDataCallback();

        $form = static::getContainer()->get('form.factory')->create($this->formType, null, []);

        $this->formType->preSetData(new FormEvent($form, $inputData));

        $options = $form->get('product')->getConfig()->getOptions();

        $this->assertEquals($expectedData['empty_value'], $options['empty_value']);
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        return [
            'null item' => [
                'inputData'     => function () {
                    return null;
                },
                'expectedData'  => function () {
                    return [
                        'empty_value' => null,
                    ];
                },
            ],
            'existing item empty product' => [
                'inputData'     => function () {
                    $requestProduct = $this->getRequestProduct(LoadRequestData::REQUEST1);

                    $requestProduct->setProduct(null);

                    return $requestProduct;
                },
                'expectedData'  => function () {
                    $requestProduct = $this->getRequestProduct(LoadRequestData::REQUEST1);

                    return [
                        'empty_value' => $this->trans(
                            'orob2b.rfpadmin.message.requestproductitem.unit.removed',
                            [
                                '{title}' => $requestProduct->getProductSku(),
                            ]
                        ),
                    ];
                },
            ],
        ];
    }

    /**
     * @param string $id
     * @return RequestProduct
     */
    protected function getRequestProduct($id)
    {
        /* @var $request Request */
        $request = $this->getReference($id);

        /* @var $requestProduct RequestProduct */
        $requestProduct = $request->getRequestProducts()->first();

        $this->assertInstanceOf('OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct', $requestProduct);

        return $requestProduct;
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

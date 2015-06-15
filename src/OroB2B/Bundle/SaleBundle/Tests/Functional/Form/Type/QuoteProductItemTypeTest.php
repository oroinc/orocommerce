<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functionsl\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductItemType;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class QuoteProductItemTypeTest extends WebTestCase
{
    /**
     * @var QuoteProductItemType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->formType     = new QuoteProductItemType($this->getContainer()->get('translator'));

        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
        ]);
    }

    /**
     * @param \Closure $inputData
     * @param \Closure $expectedData
     *
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData($inputDataCallback, $expectedDataCallback)
    {
        $inputData      = $inputDataCallback();
        $expectedData   = $expectedDataCallback();

        $form = $this->getContainer()->get('form.factory')->create($this->formType, null, []);

        $event = new FormEvent($form, $inputData);
        $this->formType->preSetData($event);

        $this->assertTrue($form->has('productUnit'));

        $options = $form->get('productUnit')->getConfig()->getOptions();

        $this->assertEquals($expectedData['choices'], $options['choices']);
        $this->assertEquals($expectedData['empty_value'], $options['empty_value']);
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        return [
            'new item' => [
                'inputData'     => function() {
                    return null;
                },
                'expectedData'  => function () {
                    return [
                        'choices'       => null,
                        'empty_value'   => null,
                    ];
                },
            ],
            'existsing item' => [
                'inputData'     => function () {
                    return $this->getQuoteProductItem(LoadQuoteData::QUOTE1);
                },
                'expectedData'  => function () {
                    $quoteProductItem = $this->getQuoteProductItem(LoadQuoteData::QUOTE1);
                    return [
                        'choices' => $this->getUnits($quoteProductItem->getQuoteProduct()->getProduct()),
                        'empty_value' => $this->trans(
                            'orob2b.sale.quoteproduct.product.removed',
                            [
                                '{title}' => $quoteProductItem->getProductUnitCode(),
                            ]
                        ),
                    ];
                },
            ],
        ];
    }

    /**
     * @param string $qid
     * @return QuoteProductItem
     */
    protected function getQuoteProductItem($qid)
    {
        /* @var $quote Quote */
        $quote = $this->getReference($qid);

        /* @var $quoteProduct QuoteProduct */
        $quoteProduct = $quote->getQuoteProducts()->first();

        $this->assertInstanceOf('OroB2B\Bundle\SaleBundle\Entity\QuoteProduct', $quoteProduct);

        /* @var $item0 QuoteProductItem */
        $item0 = $quoteProduct->getQuoteProductItems()->first();

        $this->assertInstanceOf('OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem', $item0);

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
    protected function trans($id, array $parameters = array())
    {
        return $this->getContainer()->get('translator')->trans($id, $parameters);
    }
}

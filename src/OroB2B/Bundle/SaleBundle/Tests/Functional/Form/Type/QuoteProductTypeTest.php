<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functionsl\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductType;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class QuoteProductTypeTest extends WebTestCase
{
    /**
     * @var QuoteProductItemType
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

        $this->formType = new QuoteProductType($this->getContainer()->get('translator'));

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
    public function testPreSetData(\Closure $inputDataCallback, \Closure $expectedDataCallback)
    {
        $inputData      = $inputDataCallback();
        $expectedData   = $expectedDataCallback();

        $form = $this->getContainer()->get('form.factory')->create($this->formType, null, []);

        $event = new FormEvent($form, $inputData);
        $this->formType->preSetData($event);

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
            'existsing item empty product' => [
                'inputData'     => function () {
                    $quoteProduct = $this->getQuoteProduct(LoadQuoteData::QUOTE1);

                    $quoteProduct->setProduct(null);

                    return $quoteProduct;
                },
                'expectedData'  => function () {
                    $quoteProduct = $this->getQuoteProduct(LoadQuoteData::QUOTE1);

                    return [
                        'empty_value' => $this->trans(
                            'orob2b.sale.quoteproduct.product.removed',
                            [
                                '{title}' => $quoteProduct->getProductSku(),
                            ]
                        ),
                    ];
                },
            ],
        ];

        return $data;
    }

    /**
     * @param string $qid
     * @return QuoteProduct
     */
    protected function getQuoteProduct($qid)
    {
        /* @var $quote Quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);

        /* @var $quoteProduct QuoteProduct */
        $quoteProduct = $quote->getQuoteProducts()->first();

        return $quoteProduct;
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

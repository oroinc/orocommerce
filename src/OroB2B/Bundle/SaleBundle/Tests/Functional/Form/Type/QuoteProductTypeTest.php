<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductType;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class QuoteProductTypeTest extends WebTestCase
{
    /**
     * @var QuoteProductType
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

        $this->formType = new QuoteProductType(static::getContainer()->get('translator'));

        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
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
    }

    /**
     * @param string $qid
     * @return QuoteProduct
     */
    protected function getQuoteProduct($qid)
    {
        /* @var $quote Quote */
        $quote = $this->getReference($qid);

        /* @var $quoteProduct QuoteProduct */
        $quoteProduct = $quote->getQuoteProducts()->first();

        $this->assertInstanceOf('OroB2B\Bundle\SaleBundle\Entity\QuoteProduct', $quoteProduct);

        return $quoteProduct;
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

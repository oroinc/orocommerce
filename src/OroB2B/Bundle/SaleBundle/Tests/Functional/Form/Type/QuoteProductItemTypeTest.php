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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->container    = self::getContainer();
        $this->formType     = new QuoteProductItemType();

        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
        ]);
    }

    public function testPreSetData()
    {
        foreach ($this->preSetDataProvider() as $name => $item) {
            $message = sprintf('Error with data set "%s":', $name);
            $this->preSetDataTest($item['choices'], $item['inputData'], $item['expectedData'], $message);
        }
    }

    /**
     * @param mixed $choices
     * @param mixed $inputData
     * @param mixed $expectedData
     * @param string $message
     */
    protected function preSetDataTest($choices, $inputData, $expectedData, $message = '')
    {
        $form = $this->container->get('form.factory')->create($this->formType, null, []);

        $event = new FormEvent($form, $inputData);
        $this->formType->preSetData($event);
        $this->assertEquals($expectedData, $event->getData(), $message);

        $this->assertTrue($form->has('productUnit'));

        $options = $form->get('productUnit')->getConfig()->getOptions();

        $this->assertEquals($choices, $options['choices'], $message);
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        /* @var $quote Quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);

        /* @var $quoteProduct QuoteProduct */
        $quoteProduct = $quote->getQuoteProducts()->first();

        $this->assertInstanceOf('OroB2B\Bundle\SaleBundle\Entity\QuoteProduct', $quoteProduct);

        /* @var $item1 QuoteProductItem */
        $item1 = $quoteProduct->getQuoteProductItems()->first();

        $this->assertInstanceOf('OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem', $item1);

        $choices = $this->getUnits($quoteProduct->getProduct());

        return [
            'new item' => [
                'choices'       => null,
                'inputData'     => [],
                'expectedData'  => [],
            ],
            'existsing item1' => [
                'choices'       => $choices,
                'inputData'     => clone $item1,
                'expectedData'  => clone $item1,
            ],
        ];

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
}

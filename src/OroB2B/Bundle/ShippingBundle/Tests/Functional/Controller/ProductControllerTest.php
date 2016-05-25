<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\UnitValueFormatter;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Form\Extension\ProductFormExtension;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions']);
    }

    public function testUpdateProductWithNewUnit()
    {
        $data = [
            'productUnit' => 'box',
            'weight' => [
                'value' => 42000,
                'unit' => 'lbs'
            ],
            'dimensions' => [
                'value' => [
                    'length' => 100,
                    'width' => 200,
                    'height' => 300
                ],
                'unit' => 'foot'
            ],
            'freightClass' => 'parcel'
        ];

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $product->getId()]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['orob2b_product']['unitPrecisions'][] = ['unit' => 'box', 'precision' => 0];
        $formValues['orob2b_product'][ProductFormExtension::FORM_ELEMENT_NAME][] = $data;

        $this->client->followRedirects(true);

        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Product has been saved', $crawler->html());

        $repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BShippingBundle:ProductShippingOptions');

        /** @var ProductShippingOptions $option */
        $option = $repository->findOneBy(['product' => $product, 'productUnit' => 'box']);

        $this->assertNotEmpty($option);
        $this->assertEquals($data['weight']['value'], $option->getWeight()->getValue());
        $this->assertEquals($data['weight']['unit'], $option->getWeight()->getUnit()->getCode());
        $this->assertEquals($data['dimensions']['value']['length'], $option->getDimensions()->getValue()->getLength());
        $this->assertEquals($data['dimensions']['value']['width'], $option->getDimensions()->getValue()->getWidth());
        $this->assertEquals($data['dimensions']['value']['height'], $option->getDimensions()->getValue()->getHeight());
        $this->assertEquals($data['dimensions']['unit'], $option->getDimensions()->getUnit()->getCode());
        $this->assertEquals($data['freightClass'], $option->getFreightClass()->getCode());

        return $option;
    }

    /**
     * @depends testUpdateProductWithNewUnit
     *
     * @param ProductShippingOptions $option
     */
    public function testViewProduct(ProductShippingOptions $option)
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $product->getId()]));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->assertProductShippingOptions($option, $crawler->html());
    }

    /**
     * @depends testUpdateProductWithNewUnit
     *
     * @param ProductShippingOptions $option
     */
    public function testIndexProduct(ProductShippingOptions $option)
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $response = $this->client->requestGrid(
            'products-grid',
            ['oro_segments-grid[_filter][sku][value]' => $product->getSku()]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertProductShippingOptions($option, $result['product_shipping_options']);
    }

    /**
     * @param ProductShippingOptions $option
     * @param string $html
     */
    protected function assertProductShippingOptions(ProductShippingOptions $option, $html)
    {
        /** @var UnitLabelFormatter $unitFormatter */
        $unitFormatter = $this->getContainer()->get('orob2b_product.formatter.product_unit_label');

        /** @var UnitValueFormatter $weightFormatter */
        $weightFormatter = $this->getContainer()->get('orob2b_shipping.formatter.weight_unit_value');

        /** @var UnitLabelFormatter $lengthFormatter */
        $lengthFormatter = $this->getContainer()->get('orob2b_shipping.formatter.length_unit_label');

        /** @var UnitLabelFormatter $freightFormatter */
        $freightFormatter = $this->getContainer()->get('orob2b_shipping.formatter.freight_class_label');

        $this->assertContains($unitFormatter->format($option->getProductUnit()), $html);
        $this->assertContains(
            $weightFormatter->formatShort($option->getWeight()->getValue(), $option->getWeight()->getUnit()),
            $html
        );
        $this->assertContains(
            sprintf(
                '%s x %s x %s %s',
                $option->getDimensions()->getValue()->getLength(),
                $option->getDimensions()->getValue()->getWidth(),
                $option->getDimensions()->getValue()->getHeight(),
                $lengthFormatter->format($option->getDimensions()->getUnit()->getCode(), true)
            ),
            $html
        );

        $this->assertContains($freightFormatter->format($option->getFreightClass()), $html);
    }
}

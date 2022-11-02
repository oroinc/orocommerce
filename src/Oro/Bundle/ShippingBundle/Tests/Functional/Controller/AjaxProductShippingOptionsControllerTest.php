<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Form\Extension\ProductFormExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class AjaxProductShippingOptionsControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadProductShippingOptions']);
    }

    public function testGetAvailableProductUnitFreightClassesAction()
    {
        $requiredFreightClass = 'parcel';
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
            'freightClass' => $requiredFreightClass
        ];

        /** @var Product $product */
        $product = $this->getReference('product-1');

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_product']['unitPrecisions'][] = ['unit' => 'box', 'precision' => 0];
        $formValues['oro_product'][ProductFormExtension::FORM_ELEMENT_NAME][] = $data;

        $this->client->request(
            'POST',
            $this->getUrl('oro_shipping_freight_classes'),
            array_merge($formValues, ['activeUnitCode' => 'box'])
        );

        $result = static::getJsonResponseContent($this->client->getResponse(), 200);
        $result = reset($result['units']);

        $this->assertEquals($requiredFreightClass, $result);
    }
}

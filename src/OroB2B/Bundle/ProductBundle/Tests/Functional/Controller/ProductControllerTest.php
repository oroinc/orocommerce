<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    const TEST_SKU = 'SKU-001';
    const UPDATED_SKU = 'SKU-001-updated';
    const FIRST_DUPLICATED_SKU = 'SKU-001-updated-1';
    const SECOND_DUPLICATED_SKU = 'SKU-001-updated-2';

    const STATUS = 'Disabled';
    const UPDATED_STATUS = 'Enabled';

    const INVENTORY_STATUS = 'In Stock';
    const UPDATED_INVENTORY_STATUS = 'Out of Stock';

    const FIRST_UNIT_CODE = 'item';
    const FIRST_UNIT_FULL_NAME = 'item';
    const FIRST_UNIT_PRECISION = '5';

    const SECOND_UNIT_CODE = 'kg';
    const SECOND_UNIT_FULL_NAME = 'kilogram';
    const SECOND_UNIT_PRECISION = '1';

    const DEFAULT_NAME = 'default name';
    const DEFAULT_NAME_ALTERED = 'altered default name';
    const DEFAULT_DESCRIPTION = 'default description';
    const DEFAULT_SHORT_DESCRIPTION = 'default short description';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('products-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_product[sku]'] = self::TEST_SKU;
        $form['orob2b_product[owner]'] = $this->getBusinessUnitId();

        $form['orob2b_product[inventoryStatus]'] = Product::INVENTORY_STATUS_IN_STOCK;
        $form['orob2b_product[status]'] = Product::STATUS_DISABLED;
        $form['orob2b_product[names][values][default]'] = self::DEFAULT_NAME;
        $form['orob2b_product[descriptions][values][default]'] = self::DEFAULT_DESCRIPTION;
        $form['orob2b_product[shortDescriptions][values][default]'] = self::DEFAULT_SHORT_DESCRIPTION;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Product has been saved', $html);
        $this->assertContains(self::TEST_SKU, $html);
        $this->assertContains(self::INVENTORY_STATUS, $html);
        $this->assertContains(self::STATUS, $html);
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testUpdate()
    {
        $result = $this->getProductDataBySku(self::TEST_SKU);

        $this->assertEquals(self::TEST_SKU, $result['sku']);

        $id = (int)$result['id'];
        $product = $this->getContainer()->get('doctrine')->getRepository('OroB2BProductBundle:Product')->find($id);
        $locale = $this->getLocale();
        $localizedName = $this->getLocalizedName($product, $locale);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $id]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_product' => [
                '_token' => $form['orob2b_product[_token]']->getValue(),
                'sku' => self::UPDATED_SKU,
                'owner' => $this->getBusinessUnitId(),
                'inventoryStatus' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
                'status' => Product::STATUS_ENABLED,
                'unitPrecisions' => [
                    ['unit' => self::FIRST_UNIT_CODE, 'precision' => self::FIRST_UNIT_PRECISION],
                    ['unit' => self::SECOND_UNIT_CODE, 'precision' => self::SECOND_UNIT_PRECISION],
                ],
                'names' => [
                    'values' => [
                        'default' => self::DEFAULT_NAME_ALTERED,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'descriptions' => [
                    'values' => [
                        'default' => self::DEFAULT_DESCRIPTION,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'shortDescriptions' => [
                    'values' => [
                        'default' => self::DEFAULT_SHORT_DESCRIPTION,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ]
            ],
        ];

        $this->client->followRedirects(true);

        $result = $this->client->getResponse();
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // Check product unit precisions
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $id]));

        $actualUnitPrecisions = [
            [
                'unit' => $crawler->filter('select[name="orob2b_product[unitPrecisions][0][unit]"] :selected')
                    ->html(),
                'precision' => $crawler->filter('input[name="orob2b_product[unitPrecisions][0][precision]"]')
                    ->extract('value')[0],
            ],
            [
                'unit' => $crawler->filter('select[name="orob2b_product[unitPrecisions][1][unit]"] :selected')
                    ->html(),
                'precision' => $crawler->filter('input[name="orob2b_product[unitPrecisions][1][precision]"]')
                    ->extract('value')[0],
            ],
        ];
        $expectedUnitPrecisions = [
            ['unit' => self::FIRST_UNIT_FULL_NAME, 'precision' => self::FIRST_UNIT_PRECISION],
            ['unit' => self::SECOND_UNIT_FULL_NAME, 'precision' => self::SECOND_UNIT_PRECISION],
        ];

        $this->assertEquals(
            $this->sortUnitPrecisions($expectedUnitPrecisions),
            $this->sortUnitPrecisions($actualUnitPrecisions)
        );

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains(
            self::UPDATED_SKU . ' - ' . self::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        $this->assertContains(self::UPDATED_INVENTORY_STATUS, $html);
        $this->assertContains(self::UPDATED_STATUS, $html);

        $productUnitPrecision = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BProductBundle:ProductUnitPrecision')
            ->findOneBy(['product' => $id, 'unit' => self::FIRST_UNIT_CODE]);
        $this->assertEquals(self::FIRST_UNIT_PRECISION, $productUnitPrecision->getPrecision());

        $productUnitPrecision = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BProductBundle:ProductUnitPrecision')
            ->findOneBy(['product' => $id, 'unit' => self::SECOND_UNIT_CODE]);
        $this->assertEquals(self::SECOND_UNIT_PRECISION, $productUnitPrecision->getPrecision());
    }

    /**
     * @depends testView
     * @return int
     */
    public function testDuplicate()
    {
        $this->client->followRedirects(true);

        $crawler = $this->client->getCrawler();
        $button = $crawler->filterXPath('//a[@title="Duplicate"]');
        $this->assertEquals(1, $button->count());

        $this->client->request('GET', $button->eq(0)->link()->getUri(), [], [], $this->generateWsseAuthHeader());
        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('redirectUrl', $data);

        $crawler = $this->client->request('GET', $data['redirectUrl']);
        $html = $crawler->html();
        $this->assertContains('Product has been duplicated', $html);
        $this->assertContains(
            self::FIRST_DUPLICATED_SKU . ' - ' . self::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        $this->assertContains(self::UPDATED_INVENTORY_STATUS, $html);
        $this->assertContains(self::STATUS, $html);

        $this->assertContains(
            $this->createUnitPrecisionString(self::FIRST_UNIT_FULL_NAME, self::FIRST_UNIT_PRECISION),
            $html
        );
        $this->assertContains(
            $this->createUnitPrecisionString(self::SECOND_UNIT_FULL_NAME, self::SECOND_UNIT_PRECISION),
            $html
        );

        $result = $this->getProductDataBySku(self::FIRST_DUPLICATED_SKU);

        return $result['id'];
    }

    /**
     * @depends testDuplicate
     *
     * @return int
     */
    public function testSaveAndDuplicate()
    {
        $result = $this->getProductDataBySku(self::FIRST_DUPLICATED_SKU);

        $id = (int)$result['id'];
        $product = $this->getContainer()->get('doctrine')->getRepository('OroB2BProductBundle:Product')->find($id);
        $locale = $this->getLocale();
        $localizedName = $this->getLocalizedName($product, $locale);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $id]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_duplicate',
            'orob2b_product' => [
                '_token' => $form['orob2b_product[_token]']->getValue(),
                'sku' => self::FIRST_DUPLICATED_SKU,
                'owner' => $this->getBusinessUnitId(),
                'inventoryStatus' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
                'status' => Product::STATUS_ENABLED,
                'unitPrecisions' => $form->getPhpValues()['orob2b_product']['unitPrecisions'],
                'names' => [
                    'values' => [
                        'default' => self::DEFAULT_NAME_ALTERED,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'descriptions' => [
                    'values' => [
                        'default' => self::DEFAULT_DESCRIPTION,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'shortDescriptions' => [
                    'values' => [
                        'default' => self::DEFAULT_SHORT_DESCRIPTION,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
            ],
        ];

        $this->client->followRedirects(true);

        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Product has been saved and duplicated', $html);
        $this->assertContains(
            self::SECOND_DUPLICATED_SKU . ' - ' . self::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        $this->assertContains(self::UPDATED_INVENTORY_STATUS, $html);
        $this->assertContains(self::STATUS, $html);

        $this->assertContains(
            $this->createUnitPrecisionString(self::FIRST_UNIT_FULL_NAME, self::FIRST_UNIT_PRECISION),
            $html
        );
        $this->assertContains(
            $this->createUnitPrecisionString(self::SECOND_UNIT_FULL_NAME, self::SECOND_UNIT_PRECISION),
            $html
        );

        $result = $this->getProductDataBySku(self::UPDATED_SKU);

        return $result['id'];
    }

    /**
     * @depends testSaveAndDuplicate
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_product', ['id' => $id]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @return int
     */
    protected function getBusinessUnitId()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser()->getOwner()->getId();
    }

    /**
     * @param array $unitPrecisions
     * @return array
     */
    protected function sortUnitPrecisions(array $unitPrecisions)
    {
        // prices must be sort by unit and currency
        usort(
            $unitPrecisions,
            function (array $a, array $b) {
                $unitCompare = strcmp($a['unit'], $b['unit']);
                if ($unitCompare !== 0) {
                    return $unitCompare;
                }

                return strcmp($a['precision'], $b['precision']);
            }
        );

        return $unitPrecisions;
    }

    /**
     * @param string $sku
     * @return array
     */
    private function getProductDataBySku($sku)
    {
        $response = $this->client->requestGrid(
            'products-grid',
            ['products-grid[_filter][sku][value]' => $sku]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);

        $result = reset($result['data']);
        $this->assertNotEmpty($result);

        return $result;
    }

    /**
     * @param string $name
     * @param string $precision
     * @return string
     */
    private function createUnitPrecisionString($name, $precision)
    {
        return sprintf('%s with precision %s decimal places', $name, $precision);
    }

    /**
     * @return Locale
     */
    protected function getLocale()
    {
        $locale = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BWebsiteBundle:Locale')
            ->getRepository('OroB2BWebsiteBundle:Locale')
            ->findOneBy([]);

        if (!$locale) {
            throw new \LogicException('At least one locale must be defined');
        }

        return $locale;
    }

    /**
     * @param Product $product
     * @param Locale $locale
     * @return LocalizedFallbackValue
     */
    protected function getLocalizedName(Product $product, Locale $locale)
    {
        $localizedName = null;
        foreach ($product->getNames() as $name) {
            $nameLocale = $name->getLocale();
            if ($nameLocale && $nameLocale->getId() === $locale->getId()) {
                $localizedName = $name;
                break;
            }
        }

        if (!$localizedName) {
            throw new \LogicException('At least one localized name must be defined');
        }

        return $localizedName;
    }
}

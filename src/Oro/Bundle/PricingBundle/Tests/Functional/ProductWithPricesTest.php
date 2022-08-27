<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductWithPricesTest extends WebTestCase
{
    private const TEST_SKU = 'SKU-001';

    private const PRICE_LIST_NAME = 'price_list_1';

    private const FIRST_UNIT_CODE = 'item';
    private const FIRST_UNIT_FULL_NAME = 'item';
    private const FIRST_UNIT_PRECISION = 0;
    private const SECOND_UNIT_CODE = 'kg';
    private const SECOND_UNIT_FULL_NAME = 'kilogram';
    private const SECOND_UNIT_PRECISION = 3;

    private const FIRST_QUANTITY = 10;
    private const SECOND_QUANTITY = 5.556;
    private const EXPECTED_SECOND_QUANTITY = 5.556;

    private const FIRST_PRICE_VALUE = 10;
    private const FIRST_PRICE_CURRENCY = 'USD';
    private const SECOND_PRICE_VALUE = 0.5;
    private const SECOND_PRICE_CURRENCY = 'USD';

    private const DEFAULT_NAME = 'default name';

    private const CATEGORY_ID = 1;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPriceLists::class]);
    }

    private function getDefaultFamily(): AttributeFamily
    {
        return $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);
    }

    public function testCreate(): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_create'));
        $form = $crawler->selectButton('Continue')->form();
        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'oro_product_create';
        $formValues['oro_product_step_one']['category'] = self::CATEGORY_ID;
        $formValues['oro_product_step_one']['type'] = Product::TYPE_SIMPLE;
        $formValues['oro_product_step_one']['attributeFamily'] = $this->getDefaultFamily()->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->request('POST', $this->getUrl('oro_product_create'), $formValues);

        $form = $crawler->selectButton('Save and Close')->form();

        /** @var PriceList $priceList */
        $priceList = $this->getReference(self::PRICE_LIST_NAME);

        $this->client->followRedirects(true);

        $localizations = $this->getLocalizations();

        $formData = $form->getPhpValues()['oro_product'];
        $formData = array_merge($formData, [
            '_token' => $form['oro_product[_token]']->getValue(),
            'owner'  => $this->getBusinessUnitId(),
            'sku'    => self::TEST_SKU,
            'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
            'primaryUnitPrecision' => [
                'unit'      => self::FIRST_UNIT_CODE,
                'precision' => self::FIRST_UNIT_PRECISION
            ],
            'additionalUnitPrecisions' => [

                [
                    'unit'      => self::SECOND_UNIT_CODE,
                    'precision' => self::SECOND_UNIT_PRECISION
                ]
            ],
            'prices' => [
                [
                    'priceList' => $priceList->getId(),
                    'price'     => [
                        'value'    => self::FIRST_PRICE_VALUE,
                        'currency' => self::FIRST_PRICE_CURRENCY
                    ],
                    'quantity'  => self::FIRST_QUANTITY,
                    'unit'      => self::FIRST_UNIT_CODE
                ]
            ],
            'status' => Product::STATUS_ENABLED,
            'type' => Product::TYPE_SIMPLE,
        ]);

        $formData['names']['values']['default'] = self::DEFAULT_NAME;
        foreach ($localizations as $localization) {
            $formData['names']['values']['localizations'][$localization->getId()]['fallback'] = FallbackType::SYSTEM;
        }
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), [
            'input_action' => '{"route":"oro_product_update","params":{"id":"$id"}}',
            'oro_product' => $formData
        ]);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString('Product has been saved', $crawler->html());

        $this->assertEquals(
            $priceList->getId(),
            $crawler->filter('input[name="oro_product[prices][0][priceList]"]')->extract(['value'])[0]
        );
        $this->assertEquals(
            self::FIRST_QUANTITY,
            $crawler->filter('input[name="oro_product[prices][0][quantity]"]')->extract(['value'])[0]
        );
        $this->assertEquals(
            self::FIRST_UNIT_FULL_NAME,
            $crawler->filter('select[name="oro_product[prices][0][unit]"] :selected')->html()
        );
        $this->assertEquals(
            self::FIRST_PRICE_VALUE,
            $crawler->filter('input[name="oro_product[prices][0][price][value]"]')->extract(['value'])[0]
        );
        $this->assertEquals(
            self::FIRST_PRICE_CURRENCY,
            $crawler->filter('select[name="oro_product[prices][0][price][currency]"] :selected')
                ->extract(['value'])[0]
        );

        /** @var Product $product */
        $product = $this->getContainer()->get('doctrine')
            ->getRepository(Product::class)
            ->findOneBy(['sku' => self::TEST_SKU]);
        $this->assertNotEmpty($product);

        return $product->getId();
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));

        /** @var PriceList $priceList */
        $priceList = $this->getReference(self::PRICE_LIST_NAME);

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_product[sku]'] = self::TEST_SKU;
        $form['oro_product[prices][0][priceList]'] = $priceList->getId();
        $form['oro_product[prices][0][quantity]'] = self::SECOND_QUANTITY;
        $form['oro_product[prices][0][unit]'] = self::SECOND_UNIT_CODE;
        $form['oro_product[prices][0][price][value]'] = self::SECOND_PRICE_VALUE;
        $form['oro_product[prices][0][price][currency]'] = self::SECOND_PRICE_CURRENCY;

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Product has been saved', $crawler->html());

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));

        $this->assertEquals(
            $priceList->getId(),
            $crawler->filter('input[name="oro_product[prices][0][priceList]"]')->extract(['value'])[0]
        );
        $this->assertEquals(
            self::EXPECTED_SECOND_QUANTITY,
            $crawler->filter('input[name="oro_product[prices][0][quantity]"]')->extract(['value'])[0]
        );
        $this->assertEquals(
            self::SECOND_UNIT_FULL_NAME,
            $crawler->filter('select[name="oro_product[prices][0][unit]"] :selected')->html()
        );
        $this->assertEquals(
            self::SECOND_PRICE_VALUE,
            $crawler->filter('input[name="oro_product[prices][0][price][value]"]')->extract(['value'])[0]
        );
        $this->assertEquals(
            self::SECOND_PRICE_CURRENCY,
            $crawler->filter('select[name="oro_product[prices][0][price][currency]"] :selected')
                ->extract(['value'])[0]
        );

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(int $id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();

        unset($form['oro_product[prices]']);

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Product has been saved', $crawler->html());

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));

        self::assertStringContainsString('oro_product[additionalUnitPrecisions][0]', $crawler->html());
        self::assertStringNotContainsString('oro_product[prices][0]', $crawler->html());
    }

    /**
     * @return Localization[]
     */
    private function getLocalizations(): array
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(Localization::class)
            ->findAll();
    }

    private function getBusinessUnitId(): int
    {
        return $this->getContainer()->get('security.token_storage')->getToken()->getUser()->getOwner()->getId();
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\PricingBundle\Autocomplete\ProductWithPricesSearchHandler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\HttpFoundation\Request;

class ProductWithPricesSearchHandlerTest extends FrontendWebTestCase
{
    /** @var ProductWithPricesSearchHandler */
    private $searchHandler;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([LoadLocalizationData::class]);
        $localization = $this->getFirstLocalization();
        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl('oro_frontend_localization_frontend_set_current_localization'),
            ['localization' => $localization->getId()]
        );

        $this->loadFixtures([LoadFrontendProductData::class]);
        $this->searchHandler = $this->getContainer()
            ->get('oro_pricing.form.autocomplete.product_with_prices.search_handler');

        $this->ensureSessionIsAvailable();
    }

    public function testDoesNotReturnsNotMatchingProducts()
    {
        $items = $this->searchHandler->search(md5('11112222 random string'.uniqid()), 0, 100);

        $this->assertCount(0, $items['results']);
    }

    public function testReturnsProductsWithPrices()
    {
        $items = $this->searchHandler->search('product', 0, 100);

        $this->assertNotEmpty($items);

        /**
         * @var PriceList $priceList
         */
        $priceList = $this->getClientInstance()->getContainer()->get('oro_pricing.model.price_list_request_handler')
            ->getPriceList();

        $shardManager = $this->getClientInstance()->getContainer()->get('oro_pricing.shard_manager');

        /** @var ProductPrice[] $prices */
        $prices = $this->getClientInstance()->getContainer()->get('doctrine')
            ->getRepository(ProductPrice::class)
            ->findByPriceList($shardManager, $priceList, []);

        $pricesToFind = [];

        foreach ($prices as $price) {
            if (!isset($pricesToFind[$price->getProduct()->getId()])) {
                $pricesToFind[$price->getProduct()->getId()] = [];
            }

            $pricesToFind[$price->getProduct()->getId()][] = [
                'price' => $price->getPrice()->getValue(),
                'currency' => $price->getPrice()->getCurrency(),
                'quantity' => $price->getQuantity(),
                'unit' => $price->getUnit()->getCode()
            ];
        }

        foreach ($pricesToFind as $productId => $currentPricesToFind) {
            foreach ($items['results'] as $item) {
                if ($item['id'] == $productId) {
                    $this->findPriceInItem($currentPricesToFind, $item);
                }
            }
        }
    }

    public function testReturnsLocalizedProductsNames()
    {
        $localization = $this->getFirstLocalization();

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);

        $items = $this->searchHandler->search('product', 0, 100);
        $productIds = array_column($items['results'], 'id');

        /** @var Product[] $products */
        $products = $this->getClientInstance()->getContainer()->get('doctrine')
            ->getRepository(Product::class)
            ->findById($productIds);

        foreach ($items['results'] as $item) {
            foreach ($products as $product) {
                if ($item['id'] == $product->getId()) {
                    self::assertEquals($product->getName($localization), $item['defaultName.string']);
                }
            }
        }
    }

    public function testSearchByMultipleSkus()
    {
        $skuList = [LoadProductData::PRODUCT_2, LoadProductData::PRODUCT_6];

        $request = Request::create('', Request::METHOD_POST, ['sku' => $skuList]);
        $request->setSession($this->getSession());
        $this->getContainer()
            ->get('request_stack')
            ->push($request);

        $items = $this->searchHandler->search('', 1, 5);

        $this->assertCount(2, $items['results']);
        $actualSkuList = array_map(function ($item) {
            return $item['sku'];
        }, $items['results']);
        foreach ($actualSkuList as $sku) {
            $this->assertContains($sku, $skuList);
        }
    }

    /**
     * @return Localization
     */
    private function getFirstLocalization()
    {
        $localizations = LoadLocalizationData::getLocalizations();
        $localizationCode = $localizations[0]['language'];

        return $this->getReference($localizationCode);
    }

    private function findPriceInItem(array $currentPricesToFind, array $item)
    {
        foreach ($currentPricesToFind as $priceToFind) {
            $found = false;

            foreach ($item['prices'] as $unit => $itemUnitPrices) {
                if ($unit == $priceToFind['unit']) {
                    foreach ($itemUnitPrices as $itemUnitPrice) {
                        if ($itemUnitPrice['price'] == $priceToFind['price']
                            && $itemUnitPrice['currency'] == $priceToFind['currency']
                            && $itemUnitPrice['unit'] == $priceToFind['unit']
                        ) {
                            $found = true;
                        }
                    }
                }
            }

            $this->assertTrue(
                $found,
                sprintf(
                    "Price (%d %s %s) not found.",
                    $priceToFind['price'],
                    $priceToFind['unit'],
                    $priceToFind['currency']
                )
            );
        }
    }
}

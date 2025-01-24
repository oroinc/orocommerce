<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

class LoadRequestProductItemData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $request = new Request();
        $request
            ->setFirstName('Grzegorz')
            ->setLastName('Brzeczyszczykiewicz')
            ->setEmail('test_request@example.com')
            ->setCompany('Google');

        $requestProduct1 = $this->createRequestProduct(LoadProductData::PRODUCT_1);
        $requestProduct2 = $this->createRequestProduct(LoadProductData::PRODUCT_2);
        $requestProduct3 = $this->createRequestProduct(LoadProductData::PRODUCT_3);

        $requestProductItem1 = $this->createRequestProductItem(11, 1);
        $requestProductItem2 = $this->createRequestProductItem(22, 2);
        $requestProductItem3 = $this->createRequestProductItem(33, 3);

        $requestProduct1->addRequestProductItem($requestProductItem1);
        $requestProduct1->addRequestProductItem($requestProductItem2);
        $requestProduct2->addRequestProductItem($requestProductItem3);

        $this->addReference('request-product-1', $requestProduct1);
        $this->addReference('request-product-2', $requestProduct2);
        $this->addReference('request-product-3', $requestProduct3);

        $this->addReference('request-product-1.item1', $requestProductItem1);
        $this->addReference('request-product-2.item2', $requestProductItem2);
        $this->addReference('request-product-3.item3', $requestProductItem3);

        $request->addRequestProduct($requestProduct1);
        $request->addRequestProduct($requestProduct2);
        $request->addRequestProduct($requestProduct3);
        $this->addReference('rfp.request.1', $request);

        $manager->persist($request);
        $manager->flush();
    }

    private function createRequestProduct(string $productRef): RequestProduct
    {
        $requestProduct = new RequestProduct();
        $product = $this->getReference($productRef);
        $requestProduct->setProduct($product);

        return $requestProduct;
    }

    private function createRequestProductItem(float $price, float $quantity): RequestProductItem
    {
        $requestProductItem = new RequestProductItem();
        $productUnit =  $this->getReference(LoadProductUnits::MILLILITER);

        $requestProductItem
            ->setPrice(Price::create($price, 'USD'))
            ->setQuantity($quantity)
            ->setProductUnit($productUnit);

        return $requestProductItem;
    }

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadProductData::class,
            LoadProductUnits::class,
        ];
    }
}

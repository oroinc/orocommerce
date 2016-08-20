<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

class LoadRequestProductItemsData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits',
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $this->createRequestProduct($manager, $request, $unit, $product, 'request_product.1');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Request $request
     * @param ProductUnit $unit
     * @param Product $product
     * @param string $referenceName
     */
    protected function createRequestProduct(
        ObjectManager $manager,
        Request $request,
        ProductUnit $unit,
        Product $product,
        $referenceName
    ) {
        $requestProduct = new RequestProduct();
        $requestProduct
            ->setComment('Test Notes')
            ->setProduct($product)
            ->setRequest($request)
        ;

        $request->addRequestProduct($requestProduct);
        $requestProductItem = new RequestProductItem();
        $requestProductItem
            ->setRequestProduct($requestProduct)
            ->setProductUnit($unit)
            ->setQuantity(25)
            ->setPrice(Price::create(10, 'USD'))
        ;

        $requestProduct->addRequestProductItem($requestProductItem);

        $manager->persist($requestProduct);
        $manager->persist($requestProductItem);
        $this->addReference($referenceName, $requestProduct);
    }
}

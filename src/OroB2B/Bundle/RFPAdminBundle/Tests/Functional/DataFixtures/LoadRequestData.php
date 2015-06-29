<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Model\Price;
use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

class LoadRequestData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const FIRST_NAME    = 'Grzegorz';
    const LAST_NAME     = 'Brzeczyszczykiewicz';
    const EMAIL         = 'test_request@example.com';

    const REQUEST1    = 'rfpadmin.request.1';

    const PRODUCT1  = 'product.1';
    const PRODUCT2  = 'product.2';

    const UNIT1     = 'product_unit.liter';
    const UNIT2     = 'product_unit.bottle';
    const UNIT3     = 'product_unit.box';

    const CURRENCY1 = 'sale.currency.USD';
    const CURRENCY2 = 'sale.currency.EUR';


    /**
     * @var array
     */
    protected $requests = [
        self::REQUEST1 => [
            'first_name'    => self::FIRST_NAME,
            'last_name'     => self::LAST_NAME,
            'email'         => self::EMAIL,
            'phone'         => '2-(999)507-4625',
            'company'       => 'Google',
            'role'          => 'CEO',
            'body'          => 'Hey, you!',
            'products'      => [
                self::PRODUCT1 => [
                    [
                        'quantity'  => 1,
                        'unit'      => self::UNIT1,
                        'price'     => 1,
                        'currency'  => self::CURRENCY1,
                    ],
                    [
                        'quantity'  => 2,
                        'unit'      => self::UNIT2,
                        'price'     => 2,
                        'currency'  => self::CURRENCY1,
                    ],
                ],
                self::PRODUCT2 => [
                    [
                        'quantity'  => 3,
                        'unit'      => self::UNIT3,
                        'price'     => 3,
                        'currency'  => self::CURRENCY1,
                    ]
                ],
            ],
            'comments'  => [
                self::PRODUCT1  => 'Product1 Comment',
                self::PRODUCT2  => 'Product2 Comment',
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures\LoadRequestStatusData',
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $status = $manager->getRepository('OroB2BRFPAdminBundle:RequestStatus')->findOneBy([], ['id' => 'ASC']);

        if (!$status) {
            return;
        }

        foreach ($this->requests as $key => $rawRequest) {
            $request = new Request();
            $request
                ->setFirstName($rawRequest['first_name'])
                ->setLastName($rawRequest['last_name'])
                ->setEmail($rawRequest['email'])
                ->setPhone($rawRequest['phone'])
                ->setCompany($rawRequest['company'])
                ->setRole($rawRequest['role'])
                ->setBody($rawRequest['body'])
                ->setStatus($status)
            ;

            foreach ($this->getRequestProducts($rawRequest['products'], $manager) as $product) {
                /* @var $product RequestProduct */
                $product->setComment($rawRequest['comments'][$product->getProductSku()]);
                $request->addRequestProduct($product);
            }
            $manager->persist($request);

            $this->setReference($key, $request);
        }

        $manager->flush();
    }

    /**
     * @param array $data
     * @param ObjectManager $manager
     * @return array|RequestProduct[]
     */
    protected function getRequestProducts($data, ObjectManager $manager)
    {
        $products = [];

        foreach ($data as $sku => $items) {
            $products[] = $this->getRequestProduct($sku, $items, $manager);
        }

        return $products;
    }

    /**
     * @param string $sku
     * @param array $items
     * @param ObjectManager $manager
     * @return RequestProduct
     */
    protected function getRequestProduct($sku, $items, ObjectManager $manager)
    {
        $product = new RequestProduct();
        $product
            ->setProduct($this->getReference($sku))
        ;

        foreach ($items as $item) {
            $productItem = new RequestProductItem();
            $productItem
                ->setQuantity($item['quantity'])
                ->setProductUnit($this->getReference($item['unit']))
                ->setPrice((new Price())->setValue($item['price'])->setCurrency($item['currency']))
            ;

            $manager->persist($productItem);

            $product
                ->addRequestProductItem($productItem)
            ;
        }

        $manager->persist($product);
        $manager->flush();

        return $product;
    }
}

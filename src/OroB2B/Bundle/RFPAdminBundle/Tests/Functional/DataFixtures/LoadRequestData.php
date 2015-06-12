<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Model\Price;
use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

class LoadRequestData extends AbstractFixture implements DependentFixtureInterface
{
    const FIRST_NAME = 'Grzegorz';
    const LAST_NAME = 'Brzeczyszczykiewicz';
    const EMAIL = 'test_request@example.com';

    /**
     * @var array
     */
    protected $requests = [
        [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => 'Hey, you!',
            'products'  => [
                LoadProductData::PRODUCT1 => [
                    [
                        'quantity'  => 1,
                        'unit'      => LoadProductData::UNIT1,
                        'price'     => 1,
                        'currency'  => LoadProductData::CURRENCY1,
                    ],
                    [
                        'quantity'  => 2,
                        'unit'      => LoadProductData::UNIT2,
                        'price'     => 2,
                        'currency'  => LoadProductData::CURRENCY1,
                    ],
                ],
                LoadProductData::PRODUCT2 => [
                    [
                        'quantity'  => 3,
                        'unit'      => LoadProductData::UNIT3,
                        'price'     => 3,
                        'currency'  => LoadProductData::CURRENCY1,
                    ]
                ],
                LoadProductData::PRODUCT3 => []
            ],
            'comments'  => [
                LoadProductData::PRODUCT1 => 'Product1 Comment',
                LoadProductData::PRODUCT2 => 'Product2 Comment',
                LoadProductData::PRODUCT3 => 'Product3 Comment',
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures\LoadRequestStatusData',
            'OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures\LoadProductData'
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

        foreach ($this->requests as $rawRequest) {
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

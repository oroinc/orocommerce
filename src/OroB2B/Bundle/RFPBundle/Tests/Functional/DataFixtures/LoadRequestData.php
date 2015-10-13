<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\CurrencyBundle\Model\OptionalPrice as Price;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadRequestData extends AbstractFixture implements DependentFixtureInterface
{
    const FIRST_NAME    = 'Grzegorz';
    const LAST_NAME     = 'Brzeczyszczykiewicz';
    const EMAIL         = 'test_request@example.com';

    const REQUEST1      = 'rfp.request.1';
    const REQUEST2      = 'rfp.request.2';
    const REQUEST3      = 'rfp.request.3';
    const REQUEST4      = 'rfp.request.4';
    const REQUEST5      = 'rfp.request.5';
    const REQUEST6      = 'rfp.request.6';
    const REQUEST7      = 'rfp.request.7';
    const REQUEST8      = 'rfp.request.8';

    /**
     * @var array
     */
    protected $requests = [
        self::REQUEST1 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => self::REQUEST1,
        ],
        self::REQUEST2 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => self::REQUEST2,
            'account' => LoadUserData::ACCOUNT1,
            'accountUser' => LoadUserData::ACCOUNT1_USER1,
        ],
        self::REQUEST3 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => self::REQUEST3,
            'account' => LoadUserData::ACCOUNT1,
            'accountUser' => LoadUserData::ACCOUNT1_USER2,
        ],
        self::REQUEST4 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => self::REQUEST4,
            'account' => LoadUserData::ACCOUNT1,
            'accountUser' => LoadUserData::ACCOUNT1_USER3,
        ],
        self::REQUEST5 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => self::REQUEST5,
            'account' => LoadUserData::ACCOUNT2,
            'accountUser' => LoadUserData::ACCOUNT2_USER1,
        ],
        self::REQUEST6 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => self::REQUEST6,
            'account' => LoadUserData::ACCOUNT2,
            'accountUser' => LoadUserData::ACCOUNT2_USER1,
        ],
        self::REQUEST7 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => self::REQUEST7,
            'account' => LoadUserData::ACCOUNT1,
            'accountUser' => LoadUserData::ACCOUNT1_USER1,
        ],
        self::REQUEST8 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => self::REQUEST8,
            'account' => LoadUserData::ACCOUNT1,
            'accountUser' => LoadUserData::ACCOUNT1_USER1,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData',
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisionData'
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var RequestStatus $status */
        $status = $manager->getRepository('OroB2BRFPBundle:RequestStatus')->findOneBy([], ['id' => 'ASC']);

        if (!$status) {
            return;
        }
        /** @var Organization $organization */
        $organization = $this->getUser($manager)->getOrganization();

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
                ->setOrganization($organization)
            ;
            if (!empty($rawRequest['account'])) {
                $request->setAccount($this->getReference($rawRequest['account']));
            }

            if (!empty($rawRequest['accountUser'])) {
                $request->setAccountUser($this->getReference($rawRequest['accountUser']));
            }

            $this->processRequestProducts($request, $manager);

            $manager->persist($request);
            $this->addReference($key, $request);
        }

        $manager->flush();
    }

    /**
     * @param Request $request
     * @param ObjectManager $manager
     */
    protected function processRequestProducts(Request $request, ObjectManager $manager)
    {
        $products = $this->getProducts($manager);
        $currencies = $this->getCurrencies();

        $unitPrecisions = $manager->getRepository('OroB2BProductBundle:ProductUnitPrecision')->findAll();

        $numLineItems = rand(1, 10);
        for ($i = 0; $i < $numLineItems; $i++) {
            $product = $products[array_rand($products)];

            if (!count($unitPrecisions)) {
                continue;
            }

            $requestProduct = new RequestProduct();
            $requestProduct->setProduct($product);
            $requestProduct->setComment(sprintf('Notes %s', $i));
            $numProductItems = rand(1, 10);
            for ($j = 0; $j < $numProductItems; $j++) {
                $productUnit = $unitPrecisions[array_rand($unitPrecisions)]->getUnit();

                $currency = $currencies[rand(0, count($currencies) - 1)];
                $requestProductItem = new RequestProductItem();
                $requestProductItem
                    ->setPrice(Price::create(rand(1, 100), $currency))
                    ->setQuantity(rand(1, 100))
                    ->setProductUnit($productUnit)
                ;
                $requestProduct->addRequestProductItem($requestProductItem);
            }
            $request->addRequestProduct($requestProduct);
        }
    }

    /**
     * @param ObjectManager $manager
     * @return Product[]
     */
    protected function getProducts(ObjectManager $manager)
    {
        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([], null, 10);

        if (0 === count($products)) {
            throw new \LogicException('There are no products in system');
        }

        return $products;
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        $currencies = $this->container->get('oro_config.manager')->get('oro_currency.allowed_currencies');

        if (!$currencies) {
            $currencies = (array)$this->container->get('oro_locale.settings')->getCurrency();
        }

        if (!$currencies) {
            throw new \LogicException('There are no currencies in system');
        }

        return $currencies;
    }
}

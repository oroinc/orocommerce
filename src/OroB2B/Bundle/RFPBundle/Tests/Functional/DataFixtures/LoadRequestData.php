<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadRequestData extends AbstractFixture implements DependentFixtureInterface
{
    const FIRST_NAME = 'Grzegorz';
    const FIRST_NAME_DELETED = 'John';
    const LAST_NAME = 'Brzeczyszczykiewicz';
    const EMAIL = 'test_request@example.com';
    const PO_NUMBER = 'CA1234USD';

    const REQUEST1 = 'rfp.request.1';
    const REQUEST2 = 'rfp.request.2';
    const REQUEST3 = 'rfp.request.3';
    const REQUEST4 = 'rfp.request.4';
    const REQUEST5 = 'rfp.request.5';
    const REQUEST6 = 'rfp.request.6';
    const REQUEST7 = 'rfp.request.7';
    const REQUEST8 = 'rfp.request.8';
    const REQUEST9 = 'rfp.request.9';

    /**
     * @var array
     */
    protected static $requests = [
        self::REQUEST1 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST1,
            'po_number' => self::PO_NUMBER,
            'ship_until' => true,
        ],
        self::REQUEST2 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST2,
            'account' => LoadUserData::ACCOUNT1,
            'accountUser' => LoadUserData::ACCOUNT1_USER1,
            'po_number' => self::PO_NUMBER,
            'ship_until' => true,
        ],
        self::REQUEST3 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST3,
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
            'note' => self::REQUEST4,
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
            'note' => self::REQUEST5,
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
            'note' => self::REQUEST6,
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
            'note' => self::REQUEST7,
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
            'note' => self::REQUEST8,
            'account' => LoadUserData::ACCOUNT1,
            'accountUser' => LoadUserData::ACCOUNT1_USER1,
            'po_number' => self::PO_NUMBER,
            'ship_until' => true,
        ],
        self::REQUEST9 => [
            'first_name' => self::FIRST_NAME_DELETED,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST8,
            'account' => LoadUserData::ACCOUNT1,
            'accountUser' => LoadUserData::ACCOUNT1_USER3,
            'po_number' => self::PO_NUMBER,
            'ship_until' => true,
            'deleted' => '-1 day'
        ],
    ];

    /**
     * @param string $requestFieldName
     * @param string $requestFieldValue
     * @return array
     */
    public static function getRequestsFor($requestFieldName, $requestFieldValue)
    {
        return array_filter(self::$requests, function ($request) use ($requestFieldName, $requestFieldValue) {
            return array_key_exists($requestFieldName, $request) && $request[$requestFieldName] == $requestFieldValue;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData',
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $statuses = [
            LoadRequestStatusData::NAME_CLOSED,
            LoadRequestStatusData::NAME_IN_PROGRESS,
            LoadRequestStatusData::NAME_DELETED,
            LoadRequestStatusData::NAME_NOT_DELETED,
        ];

        /** @var RequestStatus $status */
        $status = $this->getReference(LoadRequestStatusData::PREFIX . $statuses[array_rand($statuses)]);

        /** @var Organization $organization */
        $organization = $this->getUser($manager)->getOrganization();

        foreach (self::$requests as $key => $rawRequest) {
            $request = new Request();
            $request
                ->setFirstName($rawRequest['first_name'])
                ->setLastName($rawRequest['last_name'])
                ->setEmail($rawRequest['email'])
                ->setPhone($rawRequest['phone'])
                ->setCompany($rawRequest['company'])
                ->setRole($rawRequest['role'])
                ->setNote($rawRequest['note'])
                ->setStatus($status)
                ->setOrganization($organization);

            if (!empty($rawRequest['account'])) {
                $request->setAccount($this->getReference($rawRequest['account']));
            }

            if (!empty($rawRequest['accountUser'])) {
                $request->setAccountUser($this->getReference($rawRequest['accountUser']));
            }

            $this->processRequestProducts($request);
            if (isset($rawRequest['ship_until'])) {
                $request->setShipUntil(new \DateTime());
            }

            if (isset($rawRequest['po_number'])) {
                $request->setPoNumber($rawRequest['po_number']);
            }

            if (isset($rawRequest['deleted'])) {
                $request->setDeletedAt(new \DateTime($rawRequest['deleted']));
            }

            $manager->persist($request);
            $this->addReference($key, $request);
        }

        $manager->flush();
    }

    /**
     * @param Request $request
     */
    protected function processRequestProducts(Request $request)
    {
        $currencies = $this->getCurrencies();
        $products = [
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_2,
            LoadProductData::PRODUCT_3,
            LoadProductData::PRODUCT_4,
            LoadProductData::PRODUCT_5,
        ];

        $numLineItems = rand(1, 10);
        for ($i = 0; $i < $numLineItems; $i++) {
            /** @var Product $product */
            $product = $this->getReference($products[array_rand($products)]);

            $requestProduct = new RequestProduct();
            $requestProduct->setProduct($product);
            $requestProduct->setComment(sprintf('Notes %s', $i));
            $productUnitPrecisions = $product->getUnitPrecisions();
            $productUnit = $productUnitPrecisions[rand(0, count($productUnitPrecisions) - 1)]->getUnit();
            $numProductItems = rand(1, 10);
            for ($j = 0; $j < $numProductItems; $j++) {
                $currency = $currencies[rand(0, count($currencies) - 1)];
                $requestProductItem = new RequestProductItem();
                $requestProductItem
                    ->setPrice(Price::create(rand(1, 100), $currency))
                    ->setQuantity(rand(1, 100))
                    ->setProductUnit($productUnit);
                $requestProduct->addRequestProductItem($requestProductItem);
            }
            $request->addRequestProduct($requestProduct);
        }
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

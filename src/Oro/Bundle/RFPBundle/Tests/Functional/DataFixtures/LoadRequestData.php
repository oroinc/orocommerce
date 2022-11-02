<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadRequestData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

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
    const REQUEST10 = 'rfp.request.10';
    const REQUEST11 = 'rfp.request.11';
    const REQUEST12 = 'rfp.request.12';
    const REQUEST13 = 'rfp.request.13';
    const REQUEST14 = 'rfp.request.14';
    const NUM_REQUESTS = 14;
    const NUM_LINE_ITEMS = 5;
    const NUM_PRODUCTS = 5;

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
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER1,
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
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER2,
        ],
        self::REQUEST4 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST4,
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER3,
        ],
        self::REQUEST5 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST5,
            'customer' => LoadUserData::ACCOUNT2,
            'customerUser' => LoadUserData::ACCOUNT2_USER1,
        ],
        self::REQUEST6 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST6,
            'customer' => LoadUserData::ACCOUNT2,
            'customerUser' => LoadUserData::ACCOUNT2_USER1,
        ],
        self::REQUEST7 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST7,
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER1,
        ],
        self::REQUEST8 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST8,
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER1,
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
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER3,
            'po_number' => self::PO_NUMBER,
            'ship_until' => true,
            'deleted' => '-1 day'
        ],
        self::REQUEST10 => [
            'first_name' => 'PAUser1FN',
            'last_name' => 'PAUser1LN',
            'email' => LoadUserData::PARENT_ACCOUNT_USER1,
            'phone' => '2-(999)507-1234',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST10,
            'customer' => LoadUserData::PARENT_ACCOUNT,
            'customerUser' => LoadUserData::PARENT_ACCOUNT_USER1
        ],
        self::REQUEST11 => [
            'first_name' => 'PAUser2FN',
            'last_name' => 'PAUser2LN',
            'email' => LoadUserData::PARENT_ACCOUNT_USER2,
            'phone' => '2-(999)507-1456',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST11,
            'customer' => LoadUserData::PARENT_ACCOUNT,
            'customerUser' => LoadUserData::PARENT_ACCOUNT_USER2
        ],
        self::REQUEST12 => [
            'first_name' => 'PAWithoutUserFN',
            'last_name' => 'PAWithoutUserLN',
            'email' => 'test@example.com',
            'phone' => '2-(999)111-1456',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST12,
            'customer' => LoadUserData::PARENT_ACCOUNT
        ],
        self::REQUEST13 => [
            'first_name' => 'AWithoutUserFN',
            'last_name' => 'AWithoutUserLN',
            'email' => 'test@example.com',
            'phone' => '2-(999)111-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST13,
            'customer' => LoadUserData::ACCOUNT2
        ],
        self::REQUEST14 => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST14,
            'customer' => LoadUserData::ACCOUNT1,
            'customerUser' => LoadUserData::ACCOUNT1_USER1,
            'po_number' => 'deleted',
            'internal_status' => 'deleted'
        ]
    ];

    /**
     * @param string $requestFieldName
     * @param string $requestFieldValue
     *
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
            LoadUser::class,
            LoadUserData::class,
            LoadProductUnitPrecisions::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        /** @var User $owner */
        $owner = $this->getReference('user');

        /** @var Organization $organization */
        $organization = $owner->getOrganization();

        /** @var Website $website */
        $website = $manager->getRepository(Website::class)->findOneBy(['default' => true]);

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
                ->setOwner($owner)
                ->setOrganization($organization)
                ->setWebsite($website);

            if (!empty($rawRequest['customer'])) {
                $request->setCustomer($this->getReference($rawRequest['customer']));
            }

            if (!empty($rawRequest['customerUser'])) {
                $request->setCustomerUser($this->getReference($rawRequest['customerUser']));
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

        $this->updatedInternalStatus($manager);
    }

    protected function updatedInternalStatus(ObjectManager $manager)
    {
        $internalEntityClass = ExtendHelper::buildEnumValueClassName('rfp_internal_status');
        foreach (self::$requests as $key => $rawRequest) {
            if (!isset($rawRequest['internal_status'])) {
                continue;
            }

            /** @var Request $request */
            $request = $this->getReference($key);

            $enumValue = $manager->getRepository($internalEntityClass)->find($rawRequest['internal_status']);
            if (!$enumValue) {
                throw new \RuntimeException(
                    sprintf('Can\'t find InternalStatus with code "%s"', $rawRequest['internal_status'])
                );
            }

            $request->setInternalStatus($enumValue);
        }

        $manager->flush();
    }

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

        $numLineItems = self::NUM_LINE_ITEMS;
        for ($i = 0; $i < $numLineItems; $i++) {
            foreach ($products as $productRef) {
                $product = $this->getReference($productRef);
                $requestProduct = new RequestProduct();
                $requestProduct->setProduct($product);
                $requestProduct->setComment(sprintf('Notes %s', $i));
                $productUnitPrecisions = $product->getUnitPrecisions();
                $productUnit = $productUnitPrecisions[rand(0, count($productUnitPrecisions) - 1)]->getUnit();
                $currency = $currencies[rand(0, count($currencies) - 1)];
                $requestProductItem = new RequestProductItem();
                $requestProductItem
                    ->setPrice(Price::create(rand(1, 100), $currency))
                    ->setQuantity(rand(1, 100))
                    ->setProductUnit($productUnit);
                $requestProduct->addRequestProductItem($requestProductItem);
                $request->addRequestProduct($requestProduct);
            }
        }
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        return $this->container->get('oro_currency.config.currency')->getCurrencyList();
    }
}

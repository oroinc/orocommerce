<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Model\OptionalPrice as Price;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;

class LoadRequestDemoData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $requests = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData',
            'OroB2B\Bundle\RFPBundle\Migrations\Data\Demo\ORM\LoadRequestStatusDemoData',
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $statuses = $manager->getRepository('OroB2BRFPBundle:RequestStatus')->findAll();

        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $accountUsers = $this->getAccountUsers($manager);

        $locator  = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BRFPBundle/Migrations/Data/Demo/ORM/data/requests.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 5000, ',');

        while (($data = fgetcsv($handler, 5000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $accountUser = $accountUsers[rand(0, count($accountUsers) - 1)];

            $request = new Request();
            $request
                ->setFirstName($row['first_name'])
                ->setLastName($row['last_name'])
                ->setEmail($row['email'])
                ->setPhone($row['phone'])
                ->setCompany($row['company'])
                ->setRole($row['role'])
                ->setBody($row['body'])
                ->setAccountUser($accountUser)
                ->setAccount($accountUser ? $accountUser->getAccount() : null)
            ;

            $status = $statuses[rand(0, count($statuses) - 1)];
            $request->setStatus($status);
            $request->setOrganization($organization);

            $this->processRequestProducts($request, $manager);

            $manager->persist($request);
        }

        fclose($handler);

        $manager->flush();
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

    /**
     * @param ObjectManager $manager
     * @return AccountUser[]
     */
    protected function getAccountUsers(ObjectManager $manager)
    {
        return array_merge([null], $manager->getRepository('OroB2BAccountBundle:AccountUser')->findBy([], null, 10));
    }

    /**
     * @param Request $request
     * @param ObjectManager $manager
     */
    protected function processRequestProducts(Request $request, ObjectManager $manager)
    {
        $products = $this->getProducts($manager);
        $currencies = $this->getCurrencies();
        for ($i = 0; $i < rand(1, 10); $i++) {
            $product = $products[rand(0, count($products) - 1)];
            $unitPrecisions = $product->getUnitPrecisions();

            if (!count($unitPrecisions)) {
                continue;
            }

            $requestProduct = new RequestProduct();
            $requestProduct->setProduct($product);
            $requestProduct->setComment(sprintf('Notes %s', $i));
            for ($j = 0; $j < rand(1, 10); $j++) {
                $productUnit = $unitPrecisions[rand(0, count($unitPrecisions) - 1)]->getUnit();

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
}

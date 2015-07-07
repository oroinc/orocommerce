<?php

namespace OroB2B\Bundle\RFPAdminBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Model\OptionalPrice as Price;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

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
            'OroB2B\Bundle\RFPAdminBundle\Migrations\Data\Demo\ORM\LoadRequestStatusDemoData',
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $statuses = $manager->getRepository('OroB2BRFPAdminBundle:RequestStatus')->findAll();

        $locator  = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BRFPAdminBundle/Migrations/Data/Demo/ORM/data/requests.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 5000, ',');

        while (($data = fgetcsv($handler, 5000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $request = new Request();
            $request->setFirstName($row['first_name']);
            $request->setLastName($row['last_name']);
            $request->setEmail($row['email']);
            $request->setPhone($row['phone']);
            $request->setCompany($row['company']);
            $request->setRole($row['role']);
            $request->setBody($row['body']);

            $status = $statuses[rand(0, count($statuses) - 1)];
            $request->setStatus($status);

            $this->processRequestProducts($request, $manager);

            $manager->persist($request);
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Product[]
     * @throws \LogicException
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
     * @throws \LogicException
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

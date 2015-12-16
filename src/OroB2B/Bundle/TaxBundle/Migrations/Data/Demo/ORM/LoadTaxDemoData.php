<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\AddressBundle\Entity\Country;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\ZipCode;
use OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

class LoadTaxDemoData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var ContainerInterface
     */
    protected $zipCodes = [];

    /**
     * @var array|Tax[]
     */
    protected $taxes = [];

    /**
     * @var array|TaxJurisdiction[]
     */
    protected $jurisdictions = [];

    /**
     * @var array|ProductTaxCode[]
     */
    protected $productTaxCodes = [];

    /**
     * @var array|AccountTaxCode[]
     */
    protected $accountTaxCodes = [];

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
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $dirPath = $locator->locate('@OroB2BTaxBundle/Migrations/Data/Demo/ORM/tax_rates_data');
        if (is_array($dirPath)) {
            $dirPath = current($dirPath);
        }
        $this->manager = $manager;
        $country = $this->manager->getRepository('OroAddressBundle:Country')->findOneBy(['iso2Code' => 'US']);

        foreach (glob($dirPath . '/*.csv') as $filePath) {
            $handler = fopen($filePath, 'r');
            $headers = fgetcsv($handler, 1000, ',');

            while (($data = fgetcsv($handler, 1000, ',')) !== false) {
                if (count($headers) != count(array_values($data))) {
                    continue;
                }
                $row = array_combine($headers, array_values($data));

                $state = $row['State'];
                $zip = $row['ZipCode'];
                $regionName = $row['TaxRegionName'];
                $regionCode = $row['TaxRegionCode'];
                $rate = 100 * $row['CombinedRate'];

                $zipCode = $this->getZipCode($zip);
                
                $tax = $this->getTax($rate);
                
                $jurisdiction = $this->getJurisdiction($country, $state, $regionCode, $regionName, $rate);
                $jurisdiction->addZipCode($zipCode);

                $productTaxCode = $this->getProductTaxCode($regionCode, $regionName, $rate);
                
                $accountTaxCode = $this->getAccountTaxCode($regionCode, $regionName, $rate);

                $rule = new TaxRule();
                $rule->setDescription('Rule tax for ' . $regionName . ' with rate ' . $rate . '%')
                    ->setProductTaxCode($productTaxCode)
                    ->setAccountTaxCode($accountTaxCode)
                    ->setTaxJurisdiction($jurisdiction)
                    ->setTax($tax);

                $manager->persist($rule);
                $manager->flush();
            }

            fclose($handler);
        }
        $codes = array_values($this->productTaxCodes);
        $products = $this->getProducts($manager);
        foreach ($products as $product) {
            /* @var ProductTaxCode $productTaxCode */
            $productTaxCode = $codes[rand(0, count($codes) - 1)];
            $productTaxCode->addProduct($product);
        }

        $codes = array_values($this->accountTaxCodes);
        $accounts = $this->getAccounts($manager);
        foreach ($accounts as $account) {
            /* @var AccountTaxCode $accountTaxCode */
            $accountTaxCode = $codes[rand(0, count($codes) - 1)];
            $accountTaxCode->addAccount($account);
        }

        $manager->flush();
    }


    /**
     * @param string $zip
     * @return ZipCode
     */
    protected function getZipCode($zip)
    {
        if (isset($this->zipCodes[$zip])) {
            $zipCode = $this->zipCodes[$zip];
        } else {
            $zipCode = new ZipCode();
            $zipCode->setZipCode($zip);

            $this->manager->persist($zipCode);
            $this->zipCodes[$zip] = $zipCode;
        }

        return $zipCode;
    }

    /**
     * @param float $rate
     * @return Tax
     */
    protected function getTax($rate)
    {
        if (isset($this->taxes[(string)$rate])) {
            $tax = $this->taxes[(string)$rate];
        } else {
            $tax = new Tax();
            $tax
                ->setCode('TAX' . $rate)
                ->setRate($rate);

            $this->manager->persist($tax);
            $this->taxes[(string)$rate] = $tax;
        }

        return $tax;
    }

    /**
     * @param Country $country
     * @param string $state
     * @param string $regionCode
     * @param string $regionName
     * @param float $rate
     * @return TaxJurisdiction
     */
    protected function getJurisdiction($country, $state, $regionCode, $regionName, $rate)
    {
        $jurisdictionKey = $regionCode . $rate;
        if (isset($this->jurisdictions[$jurisdictionKey])) {
            $jurisdiction = $this->jurisdictions[$jurisdictionKey];
        } else {
            $region = $this->manager
                ->getRepository('OroAddressBundle:Region')
                ->findOneBy(['combinedCode' => $country->getIso2Code() . '-' . $state]);

            $jurisdiction = new TaxJurisdiction();
            $jurisdiction
                ->setCode($regionCode . $rate)
                ->setDescription('Tax jurisdiction for ' . $regionName . ' with rate ' . $rate . '%')
                ->setCountry($country)
                ->setRegion($region)
                ->setRegionText($regionName);

            $this->manager->persist($jurisdiction);
            $this->jurisdictions[$jurisdictionKey] = $jurisdiction;
        }

        return $jurisdiction;
    }

    /**
     * @param string $regionCode
     * @param string $regionName
     * @param float $rate
     * @return TaxJurisdiction
     */
    protected function getProductTaxCode($regionCode, $regionName, $rate)
    {
        $productCodeKey = 'P' . $regionCode . $rate;
        if (isset($this->productTaxCodes[$productCodeKey])) {
            $productTaxCode = $this->productTaxCodes[$productCodeKey];
        } else {
            $productTaxCode = new ProductTaxCode();
            $productTaxCode
                ->setCode($productCodeKey)
                ->setDescription('Product tax for ' . $regionName . ' with rate ' . $rate . '%');

            $this->manager->persist($productTaxCode);
            $this->productTaxCodes[$productCodeKey] = $productTaxCode;
        }

        return $productTaxCode;
    }

    /**
     * @param string $regionCode
     * @param string $regionName
     * @param float $rate
     * @return TaxJurisdiction
     */
    protected function getAccountTaxCode($regionCode, $regionName, $rate)
    {
        $accountCodeKey = 'A' . $regionCode . $rate;
        if (isset($this->accountTaxCodes[$accountCodeKey])) {
            $accountTaxCode = $this->accountTaxCodes[$accountCodeKey];
        } else {
            $accountTaxCode = new AccountTaxCode();
            $accountTaxCode
                ->setCode($accountCodeKey)
                ->setDescription('Account tax for ' . $regionName . ' with rate ' . $rate . '%');

            $this->manager->persist($accountTaxCode);
            $this->accountTaxCodes[$accountCodeKey] = $accountTaxCode;
        }

        return $accountTaxCode;
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Product[]
     */
    protected function getProducts(ObjectManager $manager)
    {
        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([], null, 10);

        if (!count($products)) {
            throw new \LogicException('There are no products in system');
        }

        return $products;
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Account[]
     */
    protected function getAccounts(ObjectManager $manager)
    {
        $accounts = $manager->getRepository('OroB2BAccountBundle:Account')->findBy([], null, 10);

        if (!count($accounts)) {
            throw new \LogicException('There are no accounts in system');
        }

        return $accounts;
    }
}

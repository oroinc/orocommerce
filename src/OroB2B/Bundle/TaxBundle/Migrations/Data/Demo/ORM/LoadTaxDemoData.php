<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;

class LoadTaxDemoData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    const BATCH_SIZE = 100;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var Connection
     */
    protected $sqlConnection;

    /**
     * @var array
     */
    protected $taxes = [];
    /**
     * @var array
     */
    protected $zipCodes = [];
    /**
     * @var array
     */
    protected $productTaxCodes = [];
    /**
     * @var array
     */
    protected $accountTaxCodes = [];

    /**
     * @var array
     */
    protected $jurisdictions = [];

    /**
     * @var array
     */
    protected $codes = [];

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var array
     */
    protected $rows = [];

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
        $this->manager = $manager;
        $this->sqlConnection = $this->container->get('doctrine.orm.entity_manager')->getConnection();
        $this->readFiles('@OroB2BTaxBundle/Migrations/Data/Demo/ORM/tax_rates_data');

        $this->setTaxes();
        $this->setJurisdictionsAndCodes();
        $this->setZipCodes();
        $this->setRules();

        $codes = array_keys($this->jurisdictions);

        $products = $this->getProducts($manager);
        foreach ($products as $product) {
            $code = $codes[rand(0, count($codes) - 1)];
            /* @var ProductTaxCode $productTaxCode */
            $productTaxCode = $this->manager
                ->getRepository('OroB2BTaxBundle:ProductTaxCode')
                ->findOneBy(['code' => $code]);

            $productTaxCode->addProduct($product);
        }

        $accounts = $this->getAccounts($manager);
        foreach ($accounts as $account) {
            $code = $codes[rand(0, count($codes) - 1)];
            /* @var AccountTaxCode $accountTaxCode */
            $accountTaxCode = $this->manager
                ->getRepository('OroB2BTaxBundle:AccountTaxCode')
                ->findOneBy(['code' => $code]);
            $accountTaxCode->addAccount($account);
        }
        $manager->flush();
    }

    /**
     * @param string $path
     */
    protected function readFiles($path)
    {
        $locator = $this->container->get('file_locator');
        $dirPath = $locator->locate($path);
        if (is_array($dirPath)) {
            $dirPath = current($dirPath);
        }

        $counter = 0;
        foreach (glob($dirPath . '/*.csv') as $filePath) {
            $handler = fopen($filePath, 'r');
            $headers = fgetcsv($handler, 1000, ',');
            while (($data = fgetcsv($handler, 1000, ',')) !== false) {
                if (count($headers) != count(array_values($data))) {
                    continue;
                }
                $fileRow = array_combine($headers, array_values($data));
                $this->indexRow($fileRow, $counter++);
            }
            fclose($handler);
        }
    }

    /**
     * @param array $fileRow
     * @param integer $counter
     */
    protected function indexRow($fileRow, $counter)
    {
        $row = [
            'state' => $fileRow['State'],
            'zip' => $fileRow['ZipCode'],
            'regionName' => $fileRow['TaxRegionName'],
            'regionCode' => $fileRow['TaxRegionCode'],
            'rate' => 100 * $fileRow['CombinedRate']
        ];
        $this->rows[$counter] = $row;

        $key = (string)$row['rate'];
        if (!isset($this->taxes[$key])) {
            $this->taxes[$key] = $counter;
        }

        $key = $row['zip'];
        if (!isset($this->zipCodes[$key])) {
            $this->zipCodes[$key] = $counter;
        }

        $key = $row['regionCode'] . $row['rate'];
        if (!isset($this->jurisdictions[$key])) {
            $this->jurisdictions[$key] = $counter;
            $this->productTaxCodes[$key] = $counter;
            $this->accountTaxCodes[$key] = $counter;
            $this->rules[$key] = $counter;
        }
    }

    protected function setTaxes()
    {
        $tableName = 'orob2b_tax';
        $tableFields = $this->implode(
            [
                'code',
                'description',
                'rate',
                'created_at',
                'updated_at'
            ]
        );
        $counter = 0;
        $batch = [];
        foreach ($this->taxes as $key => $value) {
            $batch[] = $this->implode(
                [
                    '"TAX' . $key . '"',
                    '"Tax with rate ' . $key . '%"',
                    $key,
                    'NOW()',
                    'NOW()'
                ]
            );

            if (++$counter == self::BATCH_SIZE) {
                $this->sqlInsert($tableName, $tableFields, $batch);
                $batch = [];
                $counter = 0;
            }
        }
        $this->sqlInsert($tableName, $tableFields, $batch);

        $items = $this->sqlSelect($tableName, ['rate']);
        foreach ($items as $item) {
            $this->taxes[$item['rate']] = $item['id'];
        }
    }

    protected function setJurisdictionsAndCodes()
    {
        $tableName = 'orob2b_tax_jurisdiction';
        $tableFields = $this->implode(
            [
                'country_code',
                'region_code',
                'code',
                'description',
                'region_text',
                'created_at',
                'updated_at'
            ]
        );
        $batch = [];

        $productCodeTableName = 'orob2b_tax_product_tax_code';
        $accountCodeTableName = 'orob2b_tax_account_tax_code';
        $productCodeBatch = [];
        $accountCodeBatch = [];
        $taxCodeTableFields = $this->implode(
            [
                'code',
                'description',
                'created_at',
                'updated_at'
            ]
        );

        $counter = 0;
        foreach ($this->jurisdictions as $key => $value) {
            $row = $this->rows[$value];
            $batch[] = $this->implode(
                [
                    '"US"',
                    '"US-' . $row['state'] . '"',
                    '"' . $key . '"',
                    '"Tax jurisdiction for ' . $row['regionName'] . ' with rate ' . $row['rate'] . '%"',
                    '"' . $row['regionName'] . '"',
                    'NOW()',
                    'NOW()'
                ]
            );
            $productCodeBatch[] = $this->implode(
                [
                    '"' . $key . '"',
                    '"Product tax for ' . $row['regionName'] . ' with rate ' . $row['rate'] . '%"',
                    'NOW()',
                    'NOW()'
                ]
            );
            $accountCodeBatch[] = $this->implode(
                [
                    '"' . $key . '"',
                    '"Account tax for ' . $row['regionName'] . ' with rate ' . $row['rate'] . '%"',
                    'NOW()',
                    'NOW()'
                ]
            );

            if (++$counter == self::BATCH_SIZE) {
                $this->sqlInsert($tableName, $tableFields, $batch);
                $batch = [];
                $this->sqlInsert($productCodeTableName, $taxCodeTableFields, $productCodeBatch);
                $productCodeBatch = [];
                $this->sqlInsert($accountCodeTableName, $taxCodeTableFields, $accountCodeBatch);
                $accountCodeBatch = [];
                $counter = 0;
            }
        }
        $this->sqlInsert($tableName, $tableFields, $batch);
        $this->sqlInsert($productCodeTableName, $taxCodeTableFields, $productCodeBatch);
        $this->sqlInsert($accountCodeTableName, $taxCodeTableFields, $accountCodeBatch);

        $items = $this->sqlSelect($tableName, ['code']);
        foreach ($items as $item) {
            $this->jurisdictions[$item['code']] = $item['id'];
        }

        $items = $this->sqlSelect($productCodeTableName, ['code']);
        foreach ($items as $item) {
            $this->productTaxCodes[$item['code']] = $item['id'];
        }

        $items = $this->sqlSelect($accountCodeTableName, ['code']);
        foreach ($items as $item) {
            $this->accountTaxCodes[$item['code']] = $item['id'];
        }
    }

    protected function setZipCodes()
    {
        $tableName = 'orob2b_tax_zip_code';
        $tableFields = $this->implode(
            [
                'zip_code',
                'tax_jurisdiction_id',
                'created_at',
                'updated_at'
            ]
        );
        $counter = 0;
        $batch = [];
        foreach ($this->zipCodes as $key => $value) {
            $row = $this->rows[$value];
            $batch[] = $this->implode(
                [
                    '"' . $key . '"',
                    $this->jurisdictions[$row['regionCode'] . $row['rate']],
                    'NOW()',
                    'NOW()'
                ]
            );

            if (++$counter == self::BATCH_SIZE) {
                $this->sqlInsert($tableName, $tableFields, $batch);
                $batch = [];
                $counter = 0;
            }
        }
        $this->sqlInsert($tableName, $tableFields, $batch);

        $items = $this->sqlSelect($tableName, ['zip_code']);
        foreach ($items as $item) {
            $this->zipCodes[$item['zip_code']] = $item['id'];
        }
    }

    protected function setRules()
    {
        $tableName = 'orob2b_tax_rule';
        $tableFields = $this->implode(
            [
                'product_tax_code_id',
                'account_tax_code_id',
                'tax_id',
                'tax_jurisdiction_id',
                'description',
                'created_at',
                'updated_at'
            ]
        );
        $counter = 0;
        $batch = [];
        foreach ($this->rules as $key => $value) {
            $row = $this->rows[$value];
            $batch[] = $this->implode(
                [
                    $this->productTaxCodes[$key],
                    $this->accountTaxCodes[$key],
                    $this->taxes[$row['rate']],
                    $this->jurisdictions[$key],
                    '"Tax rule for ' . $row['regionName'] . ' with rate ' . $row['rate'] . '%"',
                    'NOW()',
                    'NOW()'
                ]
            );

            if (++$counter == self::BATCH_SIZE) {
                $this->sqlInsert($tableName, $tableFields, $batch);
                $batch = [];
                $counter = 0;
            }
        }
        $this->sqlInsert($tableName, $tableFields, $batch);
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

    /**
     * @param array $data
     * @return string
     */
    protected function implode($data)
    {
        return '(' . implode(',', $data) . ')';
    }

    /**
     * @param string $tableName
     * @param string $tableFields
     * @param array $batch
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function sqlInsert($tableName, $tableFields, $batch)
    {
        $query = 'INSERT INTO ' . $tableName . ' ' . $tableFields . ' VALUES ' . implode(',', $batch);
        $this->sqlConnection->exec($query);
    }

    /**
     * @param string $tableName
     * @param array $fields
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function sqlSelect($tableName, $fields)
    {
        $stmt = $this->sqlConnection
            ->prepare('SELECT id,' . implode(',', $fields) . ' FROM ' . $tableName);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

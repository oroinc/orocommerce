<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;

class LoadAccountTaxCodeDemoData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BTaxBundle/Migrations/Data/Demo/ORM/data/account_tax_codes.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $taxCodes = [];
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $code = $row['code'];
            $description = $row['description'];

            $accountTaxCode = new AccountTaxCode();
            $accountTaxCode->setCode($code)
                ->setDescription($description);

            $manager->persist($accountTaxCode);
            $taxCodes[] = $accountTaxCode;
        }

        fclose($handler);
        $manager->flush();

        $accounts = $this->getAccounts($manager);
        foreach ($accounts as $account) {
            /* @var AccountTaxCode $accountTaxCode */
            $accountTaxCode = $taxCodes[rand(0, count($taxCodes) - 1)];
            $accountTaxCode->addAccount($account);
        }

        $manager->flush();
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

<?php

namespace OroB2B\Bundle\InvoiceBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class LoadInvoiceDemoData
 */
class LoadInvoiceDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData',
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BInvoiceBundle/Migrations/Data/Demo/ORM/data/invoices.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler);

        /** @var User $user */
        $owner = $manager->getRepository('OroUserBundle:User')->findOneBy([]);

        /** @var Account $account */
        $account = $manager->getRepository('OroB2BAccountBundle:Account')->findOneBy([]);

        while (($data = fgetcsv($handler)) !== false) {
            $row = array_combine($headers, array_values($data));

            $invoice = new Invoice();


            $invoice
                ->setOwner($owner)
                ->setAccount($account)
                ->setAccountUser($account->getUsers()->first())
                ->setOrganization($account->getOrganization())
                ->setInvoiceNumber($row['invoiceNumber'])
                ->setInvoiceDate(\DateTime::createFromFormat('Y-m-d', $row['invoiceDate'], new \DateTimeZone('UTC')))
                ->setPaymentDueDate(\DateTime::createFromFormat('Y-m-d', $row['invoiceDate'], new \DateTimeZone('UTC')))
                ->setCreatedAt(\DateTime::createFromFormat('Y-m-d', $row['invoiceDate'], new \DateTimeZone('UTC')))
                ->setUpdatedAt(\DateTime::createFromFormat('Y-m-d', $row['invoiceDate'], new \DateTimeZone('UTC')))
                ->setCurrency($row['currency'])
                ->setPoNumber($row['poNumber']);

            $manager->persist($invoice);
        }

        fclose($handler);

        $manager->flush();
    }
}

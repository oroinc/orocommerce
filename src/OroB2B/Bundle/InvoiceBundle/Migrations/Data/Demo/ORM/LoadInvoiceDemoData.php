<?php

namespace OroB2B\Bundle\InvoiceBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

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

        $website = $manager->getRepository('OroB2BWebsiteBundle:Website')
            ->findOneBy(['name' => LoadWebsiteData::DEFAULT_WEBSITE_NAME]);

        while (($data = fgetcsv($handler)) !== false) {
            $row = array_combine($headers, array_values($data));
            $row['invoiceDate'] = \DateTime::createFromFormat('Y-m-d', $row['invoiceDate'], new \DateTimeZone('UTC'));

            $invoice = new Invoice();
            $invoice
                ->setOwner($owner)
                ->setAccount($account)
                ->setAccountUser($account->getUsers()->first())
                ->setOrganization($account->getOrganization())
                ->setInvoiceNumber($row['invoiceNumber'])
                ->setInvoiceDate($row['invoiceDate'])
                ->setPaymentDueDate($row['invoiceDate'])
                ->setCreatedAt($row['invoiceDate'])
                ->setUpdatedAt($row['invoiceDate'])
                ->setCurrency($row['currency'])
                ->setPoNumber($row['poNumber'])
                ->setWebsite($website)
                ->setSubtotal(0);

            $manager->persist($invoice);
        }

        fclose($handler);

        $manager->flush();
    }
}

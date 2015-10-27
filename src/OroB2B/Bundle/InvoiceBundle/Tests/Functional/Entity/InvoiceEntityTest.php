<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Functional\Entity;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;

/**
 * Class InvoiceEntityTest
 */
class InvoiceEntityTest extends WebTestCase
{

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * @return Invoice
     */
    private function createNewInvoice()
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $invoice = new Invoice();
        //TODO: Will be changed to prePersist and preUpdate events
        $invoice->setCreatedAt(new \DateTime());
        $invoice->setUpdatedAt(new \DateTime());
        $invoice->setPaymentDueDate(new \DateTime());

        /** @var Account $account */
        $account = $entityManager
            ->getRepository('OroB2BAccountBundle:Account')
            ->findOneBy([]);

        $invoice->setAccount($account);


        return $invoice;
    }


    public function testInvoiceNumberGenerator()
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $invoice = $this->createNewInvoice();

        $entityManager->persist($invoice);
        $entityManager->flush();

        $entityManager->refresh($invoice);

        $this->assertEquals($invoice->getId(), $invoice->getInvoiceNumber());

        $invoiceNumber = uniqid('invoice-');
        $invoice = $this->createNewInvoice();
        $invoice->setInvoiceNumber($invoiceNumber);

        $entityManager->persist($invoice);
        $entityManager->flush();

        $entityManager->refresh($invoice);

        $this->assertEquals($invoiceNumber, $invoice->getInvoiceNumber());
    }


    public function testEntityFields()
    {
        $this->markTestIncomplete('Entity fields test not done yet');
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $product = $entityManager
            ->getRepository('OroB2BProductBundle:Product')
            ->findOneBy([]);

        $invoice = $this->createNewInvoice();
        $account = $invoice->getAccount();
        $invoice->setAccountUser($account->getUsers()->first());
        $invoice->setOrganization($account->getOrganization());


        $invoiceLineItem = new InvoiceLineItem();
        $invoiceLineItem->setProduct($product);
        $invoiceLineItem->setInvoice($invoice);

        $entityManager->persist($invoiceLineItem);

        $entityManager->persist($invoice);
        $entityManager->flush();
    }
}

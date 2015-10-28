<?php

namespace OroB2B\Bundle\InvoiceBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadInvoiceLineItemDemoData
 */
class LoadInvoiceLineItemDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{

    /** @var ContainerInterface */
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
            'OroB2B\Bundle\InvoiceBundle\Migrations\Data\Demo\ORM\LoadInvoiceDemoData',
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BInvoiceBundle/Migrations/Data/Demo/ORM/data/invoice-line-items.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $invoicesData = array();
        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler);

        while (($data = fgetcsv($handler)) !== false) {
            $row = array_combine($headers, array_values($data));
            $invoicesData[$row['invoice']][] = $row;

        }

        foreach ($invoicesData as $invoiceNumber => $invoiceData) {
            $invoice = $manager
                ->getRepository('OroB2BInvoiceBundle:Invoice')
                ->findOneBy(['invoiceNumber' => $invoiceNumber]);
            if (is_null($invoice)) {
                continue;
            }
            foreach ($invoiceData as $invoiceLineItemData) {
                $lineItem = new InvoiceLineItem();
                $lineItem->setInvoice($invoice);
                $lineItem->setQuantity($invoiceLineItemData['quantity']);
                $lineItem->setPrice($invoiceLineItemData['price']);

                if (!empty($invoiceLineItemData['freeFormProduct'])) {
                    $lineItem->setFreeFormProduct($invoiceLineItemData['freeFormProduct']);
                    $lineItem->setProductSku($invoiceLineItemData['productSku']);
                } else {
                    /** @var Product $product */
                    $product = $manager
                        ->getRepository('OroB2BProductBundle:Product')
                        ->findOneBySku($invoiceLineItemData['productSku']);
                    $lineItem->setProduct($product);
                    $lineItem->setProductSku($product->getSku());
                }
                $manager->persist($lineItem);
            }
        }

        fclose($handler);

        $manager->flush();
    }
}

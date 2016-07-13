<?php

namespace OroB2B\Bundle\InvoiceBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

use Oro\Bundle\CurrencyBundle\Entity\Price;

class LoadInvoiceLineItemDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

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

        $invoicesData = [];
        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler);

        while (($data = fgetcsv($handler)) !== false) {
            $row = array_combine($headers, array_values($data));
            $invoicesData[$row['invoice']][] = $row;
        }

        $subtotalProvider = $this->container->get('orob2b_pricing.subtotal_processor.provider.subtotal_line_item');

        foreach ($invoicesData as $invoiceNumber => $invoiceData) {
            /** @var Invoice $invoice */
            $invoice = $manager
                ->getRepository('OroB2BInvoiceBundle:Invoice')
                ->findOneBy(['invoiceNumber' => $invoiceNumber]);
            if (is_null($invoice)) {
                continue;
            }
            foreach ($invoiceData as $invoiceLineItemData) {
                $lineItem = new InvoiceLineItem();
                $lineItem->setInvoice($invoice)
                    ->setQuantity($invoiceLineItemData['quantity'])
                    ->setSortOrder($invoiceLineItemData['sortOrder'])
                    ->setPrice(
                        Price::create((float)$invoiceLineItemData['price'], $invoice->getCurrency())
                    )
                    ->setProductUnit(
                        $manager->getReference('OroB2BProductBundle:ProductUnit', $invoiceLineItemData['productUnit'])
                    );

                if (!empty($invoiceLineItemData['freeFormProduct'])) {
                    $lineItem->setFreeFormProduct($invoiceLineItemData['freeFormProduct'])
                        ->setProductSku($invoiceLineItemData['productSku']);
                } else {
                    /** @var Product $product */
                    $product = $manager
                        ->getRepository('OroB2BProductBundle:Product')
                        ->findOneBySku($invoiceLineItemData['productSku']);
                    $lineItem->setProduct($product);
                    $lineItem->setProductSku($product->getSku());
                }

                $invoice->addLineItem($lineItem);
                $manager->persist($lineItem);
            }

            $subtotal = $subtotalProvider
                ->getSubtotal($invoice);
            $invoice->setSubtotal($subtotal->getAmount());
        }

        fclose($handler);

        $manager->flush();
    }
}

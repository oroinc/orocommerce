<?php

namespace OroB2B\Bundle\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\Form\Type\InvoiceType;
use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class InvoiceController extends Controller
{
    /**
     * @Route("/", name="orob2b_invoice_index")
     * @Template()
     * @AclAncestor("orob2b_invoice_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_invoice.entity.invoice.class'),
            'gridName' => 'invoices-grid'
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_invoice_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_invoice_view")
     *
     * @param Invoice $invoice
     *
     * @return array
     */
    public function infoAction(Invoice $invoice)
    {
        return [
            'entity' => $invoice,
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_invoice_view", requirements={"id"="\d+"})
     * @Template()
     * @Acl(
     *      id="orob2b_invoice_view",
     *      type="entity",
     *      class="OroB2BInvoiceBundle:Invoice",
     *      permission="VIEW"
     * )
     *
     * @param Invoice $invoice
     *
     * @return array
     */
    public function viewAction(Invoice $invoice)
    {
        return [
            'entity' => $invoice,
        ];
    }

    /**
     * Create invoice form
     *
     * @Route("/create", name="orob2b_invoice_create")
     * @Template("OroB2BInvoiceBundle:Invoice:update.html.twig")
     * @Acl(
     *      id="orob2b_invoice_create",
     *      type="entity",
     *      class="OroB2BInvoiceBundle:Invoice",
     *      permission="CREATE"
     * )
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        $invoice = new Invoice();
        $invoice->setWebsite($this->get('orob2b_website.manager')->getCurrentWebsite());
        //TODO: BB-3824 Change the getting currency from system configuration
        $invoice->setCurrency($this->get('oro_locale.settings')->getCurrency());

        return $this->update($invoice);
    }

    /**
     * Update invoice form
     *
     * @Route("/update/{id}", name="orob2b_invoice_update")
     * @Template("OroB2BInvoiceBundle:Invoice:update.html.twig")
     * @Acl(
     *      id="orob2b_invoice_update",
     *      type="entity",
     *      class="OroB2BInvoiceBundle:Invoice",
     *      permission="EDIT"
     * )
     * @param Invoice $invoice
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Invoice $invoice)
    {
        return $this->update($invoice);
    }

    /**
     * @param Invoice $invoice
     * @return array|RedirectResponse
     */
    protected function update(Invoice $invoice)
    {
        $form = $this->createForm(InvoiceType::NAME, $invoice);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $invoice,
            $form,
            function (Invoice $invoice) {
                return [
                    'route' => 'orob2b_invoice_update',
                    'parameters' => ['id' => $invoice->getId()],
                ];
            },
            function (Invoice $invoice) {
                return [
                    'route' => 'orob2b_invoice_view',
                    'parameters' => ['id' => $invoice->getId()],
                ];
            },
            $this->get('translator')->trans('orob2b.invoice.controller.invoice.saved.message'),
            null,
            function (Invoice $invoice, FormInterface $form, Request $request) {
                return [
                    'form' => $form->createView(),
                    'entity' => $invoice,
                    'totals' => $this->getTotalProcessor()->getTotalWithSubtotalsAsArray($invoice),
                    'isWidgetContext' => (bool)$request->get('_wid', false),
                    'tierPrices' => $this->getTierPrices($invoice),
                    'matchedPrices' => $this->getMatchedPrices($invoice),
                ];
            }
        );
    }

    /**
     * @param Invoice $invoice
     * @return array
     */
    protected function getTierPrices(Invoice $invoice)
    {
        $tierPrices = [];

        $productIds = $invoice->getLineItems()->filter(
            function (InvoiceLineItem $lineItem) {
                return $lineItem->getProduct() !== null;
            }
        )->map(
            function (InvoiceLineItem $lineItem) {
                return $lineItem->getProduct()->getId();
            }
        );

        if ($productIds) {
            $tierPrices = $this->get('orob2b_pricing.provider.combined_product_price')
                ->getPriceByPriceListIdAndProductIds(
                    $this->get('orob2b_pricing.model.price_list_request_handler')->getPriceListByAccount()->getId(),
                    $productIds->toArray(),
                    $invoice->getCurrency()
                );
        }

        return $tierPrices;
    }

    /**
     * @param Invoice $invoice
     * @return array|Price[]
     */
    protected function getMatchedPrices(Invoice $invoice)
    {
        $matchedPrices = [];

        $productsPriceCriteria = $invoice->getLineItems()->filter(
            function (InvoiceLineItem $lineItem) {
                return $lineItem->getProduct() && $lineItem->getProductUnit() && $lineItem->getQuantity();
            }
        )->map(
            function (InvoiceLineItem $lineItem) use ($invoice) {
                return new ProductPriceCriteria(
                    $lineItem->getProduct(),
                    $lineItem->getProductUnit(),
                    $lineItem->getQuantity(),
                    $invoice->getCurrency()
                );
            }
        );

        if ($productsPriceCriteria) {
            $matchedPrices = $this->get('orob2b_pricing.provider.combined_product_price')->getMatchedPrices(
                $productsPriceCriteria->toArray(),
                $this->get('orob2b_pricing.model.price_list_request_handler')->getPriceListByAccount()
            );
        }

        /** @var Price $price */
        foreach ($matchedPrices as &$price) {
            if ($price) {
                $price = [
                    'value' => $price->getValue(),
                    'currency' => $price->getCurrency()
                ];
            }
        }

        return $matchedPrices;
    }

    /**
     * @return TotalProcessorProvider
     */
    protected function getTotalProcessor()
    {
        return $this->get('orob2b_pricing.subtotal_processor.total_processor_provider');
    }
}

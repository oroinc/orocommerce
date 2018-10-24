<?php

namespace Oro\Bundle\InvoiceBundle\Controller;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use Oro\Bundle\InvoiceBundle\Form\Type\InvoiceType;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class InvoiceController extends Controller
{
    /**
     * @Route("/", name="oro_invoice_index")
     * @Template()
     * @AclAncestor("oro_invoice_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_invoice.entity.invoice.class'),
            'gridName' => 'invoices-grid'
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_invoice_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_invoice_view")
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
     * @Route("/view/{id}", name="oro_invoice_view", requirements={"id"="\d+"})
     * @Template()
     * @Acl(
     *      id="oro_invoice_view",
     *      type="entity",
     *      class="OroInvoiceBundle:Invoice",
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
     * @Route("/create", name="oro_invoice_create")
     * @Template("OroInvoiceBundle:Invoice:update.html.twig")
     * @Acl(
     *      id="oro_invoice_create",
     *      type="entity",
     *      class="OroInvoiceBundle:Invoice",
     *      permission="CREATE"
     * )
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        $invoice = new Invoice();
        $invoice->setWebsite($this->get('oro_website.manager')->getDefaultWebsite());
        $invoice->setCurrency($this->get('oro_currency.config.currency')->getDefaultCurrency());

        return $this->update($invoice);
    }

    /**
     * Update invoice form
     *
     * @Route("/update/{id}", name="oro_invoice_update")
     * @Template("OroInvoiceBundle:Invoice:update.html.twig")
     * @Acl(
     *      id="oro_invoice_update",
     *      type="entity",
     *      class="OroInvoiceBundle:Invoice",
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
        $form = $this->createForm(InvoiceType::class, $invoice);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $invoice,
            $form,
            function (Invoice $invoice) {
                return [
                    'route' => 'oro_invoice_update',
                    'parameters' => ['id' => $invoice->getId()],
                ];
            },
            function (Invoice $invoice) {
                return [
                    'route' => 'oro_invoice_view',
                    'parameters' => ['id' => $invoice->getId()],
                ];
            },
            $this->get('translator')->trans('oro.invoice.controller.invoice.saved.message'),
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

        $products = $invoice->getLineItems()->filter(
            function (InvoiceLineItem $lineItem) {
                return $lineItem->getProduct() !== null;
            }
        )->map(
            function (InvoiceLineItem $lineItem) {
                return $lineItem->getProduct();
            }
        );

        if ($products->count() > 0) {
            $scopeCriteria = $this->getPriceScopeCriteria();
            $scopeCriteria->setContext($invoice);

            /** @var ProductPriceProviderInterface $priceProvider */
            $priceProvider = $this->get('oro_pricing.provider.product_price');
            $tierPrices = $priceProvider->getPricesByScopeCriteriaAndProducts(
                $scopeCriteria,
                $products->toArray(),
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
            /** @var ProductPriceProviderInterface $priceProvider */
            $priceProvider = $this->get('oro_pricing.provider.product_price');
            $matchedPrices = $priceProvider->getMatchedPrices(
                $productsPriceCriteria->toArray(),
                $this->getPriceScopeCriteria()
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
        return $this->get('oro_pricing.subtotal_processor.total_processor_provider');
    }

    /**
     * @return ProductPriceScopeCriteriaInterface
     */
    protected function getPriceScopeCriteria(): ProductPriceScopeCriteriaInterface
    {
        return  $this->get('oro_pricing.model.product_price_scope_criteria_request_handler')->getPriceScopeCriteria();
    }
}

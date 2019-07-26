<?php

namespace Oro\Bundle\InvoiceBundle\Controller;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use Oro\Bundle\InvoiceBundle\Form\Type\InvoiceType;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds actions to list, view, create and edit invoices
 */
class InvoiceController extends AbstractController
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
            'entity_class' => Invoice::class,
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
        $invoice->setWebsite($this->get(WebsiteManager::class)->getDefaultWebsite());
        $invoice->setCurrency($this->get(CurrencyProviderInterface::class)->getDefaultCurrency());

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

        return $this->get(UpdateHandler::class)->handleUpdate(
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
            $this->get(TranslatorInterface::class)->trans('oro.invoice.controller.invoice.saved.message'),
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

            $priceProvider = $this->get(ProductPriceProviderInterface::class);
            $tierPrices = $priceProvider->getPricesByScopeCriteriaAndProducts(
                $scopeCriteria,
                $products->toArray(),
                [$invoice->getCurrency()]
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
            $priceProvider = $this->get(ProductPriceProviderInterface::class);
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
        return $this->get(TotalProcessorProvider::class);
    }

    /**
     * @return ProductPriceScopeCriteriaInterface
     */
    protected function getPriceScopeCriteria(): ProductPriceScopeCriteriaInterface
    {
        return  $this->get(ProductPriceScopeCriteriaRequestHandler::class)->getPriceScopeCriteria();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandler::class,
                CurrencyProviderInterface::class,
                TotalProcessorProvider::class,
                ProductPriceScopeCriteriaRequestHandler::class,
                WebsiteManager::class,
                ProductPriceProviderInterface::class
            ]
        );
    }
}

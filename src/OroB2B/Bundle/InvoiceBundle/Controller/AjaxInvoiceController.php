<?php

namespace OroB2B\Bundle\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\Form\Type\InvoiceType;

class AjaxInvoiceController extends Controller
{
    /**
     * @Route("/subtotal", name="orob2b_invoice_subtotal", methods={"POST"})
     * @AclAncestor("orob2b_invoice_view")
     *
     * @param Request $request
     * @return array
     */
    public function subtotalAction(Request $request)
    {
        $invoiceClass = $this->getParameter('orob2b_invoice.entity.invoice.class');
        $id = $request->get('id');

        if ($id) {
            /** @var Invoice $invoice */
            $invoice = $this->getDoctrine()->getManagerForClass($invoiceClass)->find($invoiceClass, $id);
        } else {
            $invoice = new $invoiceClass();
        }

        if (null !== $request->get(InvoiceType::NAME)) {
            $form = $this->createForm(InvoiceType::NAME, $invoice);
            $form->submit($request);
        }

        $subtotal = $this->get('orob2b_pricing.subtotal_processor.provider.subtotal_line_item')->getSubtotal($invoice);

        return new JsonResponse(
            [
                'subtotals' => [
                    'subtotal' => $subtotal->toArray(),
                ],
            ]
        );
    }
}

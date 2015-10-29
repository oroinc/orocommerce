<?php

namespace OroB2B\Bundle\InvoiceBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class InvoiceController
 */
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
}

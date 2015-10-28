<?php

namespace OroB2B\Bundle\InvoiceBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
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
}

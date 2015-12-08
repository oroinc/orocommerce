<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Storage\RequestDataStorage;

class QuoteController extends Controller
{
    /**
     * @Route("/create/{id}", name="orob2b_rfp_quote_create", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_sale_quote_create")
     *
     * @param RFPRequest $rfpRequest
     *
     * @return RedirectResponse
     */
    public function createAction(RFPRequest $rfpRequest)
    {
        /** @var RequestDataStorage $storageService */
        $storageService = $this->get('orob2b_rfp.service.request_data_storage');
        $storageService->saveToStorage($rfpRequest);

        return $this->redirectToRoute('orob2b_sale_quote_create', [ProductDataStorage::STORAGE_KEY => true]);
    }
}

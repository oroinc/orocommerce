<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeType;

class ProductTaxCodeController extends Controller
{
    /**
     * @Route("/", name="orob2b_tax_product_tax_code_index")
     * @Template
     * @AclAncestor("orob2b_tax_product_tax_code_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_tax.entity.product_tax_code.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_tax_product_tax_code_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_tax_product_tax_code_view",
     *      type="entity",
     *      class="OroTaxBundle:ProductTaxCode",
     *      permission="VIEW"
     * )
     *
     * @param ProductTaxCode $productTaxCode
     * @return array
     */
    public function viewAction(ProductTaxCode $productTaxCode)
    {
        return [
            'entity' => $productTaxCode
        ];
    }

    /**
     * @Route("/create", name="orob2b_tax_product_tax_code_create")
     * @Template("OroTaxBundle:ProductTaxCode:update.html.twig")
     * @Acl(
     *      id="orob2b_tax_product_tax_code_create",
     *      type="entity",
     *      class="OroTaxBundle:ProductTaxCode",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new ProductTaxCode());
    }

    /**
     * @Route("/update/{id}", name="orob2b_tax_product_tax_code_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_tax_product_tax_code_update",
     *      type="entity",
     *      class="OroTaxBundle:ProductTaxCode",
     *      permission="EDIT"
     * )
     *
     * @param ProductTaxCode $productTaxCode
     * @return array
     */
    public function updateAction(ProductTaxCode $productTaxCode)
    {
        return $this->update($productTaxCode);
    }

    /**
     * @param ProductTaxCode $productTaxCode
     * @return array|RedirectResponse
     */
    protected function update(ProductTaxCode $productTaxCode)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $productTaxCode,
            $this->createForm(ProductTaxCodeType::NAME, $productTaxCode),
            function (ProductTaxCode $productTaxCode) {
                return [
                    'route' => 'orob2b_tax_product_tax_code_update',
                    'parameters' => ['id' => $productTaxCode->getId()]
                ];
            },
            function (ProductTaxCode $productTaxCode) {
                return [
                    'route' => 'orob2b_tax_product_tax_code_view',
                    'parameters' => ['id' => $productTaxCode->getId()]
                ];
            },
            $this->get('translator')->trans('oro.tax.controller.product_tax_code.saved.message')
        );
    }
}

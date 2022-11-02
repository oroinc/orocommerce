<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for product tax codes.
 */
class ProductTaxCodeController extends AbstractController
{
    /**
     * @Route("/", name="oro_tax_product_tax_code_index")
     * @Template
     * @AclAncestor("oro_tax_product_tax_code_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => ProductTaxCode::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_tax_product_tax_code_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_product_tax_code_view",
     *      type="entity",
     *      class="OroTaxBundle:ProductTaxCode",
     *      permission="VIEW"
     * )
     */
    public function viewAction(ProductTaxCode $productTaxCode): array
    {
        return [
            'entity' => $productTaxCode
        ];
    }

    /**
     * @Route("/create", name="oro_tax_product_tax_code_create")
     * @Template("@OroTax/ProductTaxCode/update.html.twig")
     * @Acl(
     *      id="oro_tax_product_tax_code_create",
     *      type="entity",
     *      class="OroTaxBundle:ProductTaxCode",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new ProductTaxCode());
    }

    /**
     * @Route("/update/{id}", name="oro_tax_product_tax_code_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_product_tax_code_update",
     *      type="entity",
     *      class="OroTaxBundle:ProductTaxCode",
     *      permission="EDIT"
     * )
     */
    public function updateAction(ProductTaxCode $productTaxCode): array|RedirectResponse
    {
        return $this->update($productTaxCode);
    }

    protected function update(ProductTaxCode $productTaxCode): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $productTaxCode,
            $this->createForm(ProductTaxCodeType::class, $productTaxCode),
            $this->get(TranslatorInterface::class)->trans('oro.tax.controller.product_tax_code.saved.message')
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}

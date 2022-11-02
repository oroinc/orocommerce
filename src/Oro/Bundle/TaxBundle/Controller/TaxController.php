<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Form\Type\TaxType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tax rates.
 */
class TaxController extends AbstractController
{
    /**
     * @Route("/", name="oro_tax_index")
     * @Template
     * @AclAncestor("oro_tax_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => Tax::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_tax_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_view",
     *      type="entity",
     *      class="OroTaxBundle:Tax",
     *      permission="VIEW"
     * )
     */
    public function viewAction(Tax $tax): array
    {
        return [
            'entity' => $tax
        ];
    }

    /**
     * @Route("/create", name="oro_tax_create")
     * @Template("@OroTax/Tax/update.html.twig")
     * @Acl(
     *      id="oro_tax_create",
     *      type="entity",
     *      class="OroTaxBundle:Tax",
     *      permission="CREATE"
     * )
     */
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new Tax());
    }

    /**
     * @Route("/update/{id}", name="oro_tax_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_update",
     *      type="entity",
     *      class="OroTaxBundle:Tax",
     *      permission="EDIT"
     * )
     */
    public function updateAction(Tax $tax): array|RedirectResponse
    {
        return $this->update($tax);
    }

    protected function update(Tax $tax): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $tax,
            $this->createForm(TaxType::class, $tax),
            $this->get(TranslatorInterface::class)->trans('oro.tax.controller.tax.saved.message')
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

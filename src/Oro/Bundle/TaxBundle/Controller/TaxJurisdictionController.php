<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Form\Type\TaxJurisdictionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tax jurisdictions.
 */
class TaxJurisdictionController extends AbstractController
{
    /**
     * @Route("/", name="oro_tax_jurisdiction_index")
     * @Template
     * @AclAncestor("oro_tax_jurisdiction_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => TaxJurisdiction::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_tax_jurisdiction_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_jurisdiction_view",
     *      type="entity",
     *      class="OroTaxBundle:TaxJurisdiction",
     *      permission="VIEW"
     * )
     *
     * @param TaxJurisdiction $taxJurisdiction
     * @return array
     */
    public function viewAction(TaxJurisdiction $taxJurisdiction)
    {
        return [
            'entity' => $taxJurisdiction
        ];
    }

    /**
     * @Route("/create", name="oro_tax_jurisdiction_create")
     * @Template("@OroTax/TaxJurisdiction/update.html.twig")
     * @Acl(
     *      id="oro_tax_jurisdiction_create",
     *      type="entity",
     *      class="OroTaxBundle:TaxJurisdiction",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new TaxJurisdiction());
    }

    /**
     * @Route("/update/{id}", name="oro_tax_jurisdiction_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_jurisdiction_update",
     *      type="entity",
     *      class="OroTaxBundle:TaxJurisdiction",
     *      permission="EDIT"
     * )
     *
     * @param TaxJurisdiction $taxJurisdiction
     * @return array
     */
    public function updateAction(TaxJurisdiction $taxJurisdiction)
    {
        return $this->update($taxJurisdiction);
    }

    /**
     * @param TaxJurisdiction $taxJurisdiction
     * @return array|RedirectResponse
     */
    protected function update(TaxJurisdiction $taxJurisdiction)
    {
        return $this->get(UpdateHandler::class)->handleUpdate(
            $taxJurisdiction,
            $this->createForm(TaxJurisdictionType::class, $taxJurisdiction),
            function (TaxJurisdiction $taxJurisdiction) {
                return [
                    'route' => 'oro_tax_jurisdiction_update',
                    'parameters' => ['id' => $taxJurisdiction->getId()]
                ];
            },
            function (TaxJurisdiction $taxJurisdiction) {
                return [
                    'route' => 'oro_tax_jurisdiction_view',
                    'parameters' => ['id' => $taxJurisdiction->getId()]
                ];
            },
            $this->get(TranslatorInterface::class)->trans('oro.tax.controller.tax_jurisdiction.saved.message')
        );
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
            ]
        );
    }
}

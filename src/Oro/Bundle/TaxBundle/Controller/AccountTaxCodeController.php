<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\AccountTaxCodeType;

class AccountTaxCodeController extends Controller
{
    /**
     * @Route("/", name="orob2b_tax_account_tax_code_index")
     * @Template
     * @AclAncestor("orob2b_tax_account_tax_code_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_tax.entity.account_tax_code.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_tax_account_tax_code_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_tax_account_tax_code_view",
     *      type="entity",
     *      class="OroTaxBundle:AccountTaxCode",
     *      permission="VIEW"
     * )
     *
     * @param AccountTaxCode $accountTaxCode
     * @return array
     */
    public function viewAction(AccountTaxCode $accountTaxCode)
    {
        return [
            'entity' => $accountTaxCode
        ];
    }

    /**
     * @Route("/create", name="orob2b_tax_account_tax_code_create")
     * @Template("OroTaxBundle:AccountTaxCode:update.html.twig")
     * @Acl(
     *      id="orob2b_tax_account_tax_code_create",
     *      type="entity",
     *      class="OroTaxBundle:AccountTaxCode",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new AccountTaxCode());
    }

    /**
     * @Route("/update/{id}", name="orob2b_tax_account_tax_code_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_tax_account_tax_code_update",
     *      type="entity",
     *      class="OroTaxBundle:AccountTaxCode",
     *      permission="EDIT"
     * )
     *
     * @param AccountTaxCode $accountTaxCode
     * @return array
     */
    public function updateAction(AccountTaxCode $accountTaxCode)
    {
        return $this->update($accountTaxCode);
    }

    /**
     * @param AccountTaxCode $accountTaxCode
     * @return array|RedirectResponse
     */
    protected function update(AccountTaxCode $accountTaxCode)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountTaxCode,
            $this->createForm(AccountTaxCodeType::NAME, $accountTaxCode),
            function (AccountTaxCode $accountTaxCode) {
                return [
                    'route' => 'orob2b_tax_account_tax_code_update',
                    'parameters' => ['id' => $accountTaxCode->getId()]
                ];
            },
            function (AccountTaxCode $accountTaxCode) {
                return [
                    'route' => 'orob2b_tax_account_tax_code_view',
                    'parameters' => ['id' => $accountTaxCode->getId()]
                ];
            },
            $this->get('translator')->trans('oro.tax.controller.account_tax_code.saved.message')
        );
    }
}

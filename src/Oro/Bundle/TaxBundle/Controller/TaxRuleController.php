<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Form\Type\TaxRuleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tax rules.
 */
class TaxRuleController extends AbstractController
{
    #[Route(path: '/', name: 'oro_tax_rule_index')]
    #[Template]
    #[AclAncestor('oro_tax_rule_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => TaxRule::class
        ];
    }

    #[Route(path: '/view/{id}', name: 'oro_tax_rule_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_tax_rule_view', type: 'entity', class: TaxRule::class, permission: 'VIEW')]
    public function viewAction(TaxRule $taxRule): array
    {
        return [
            'entity' => $taxRule
        ];
    }

    #[Route(path: '/create', name: 'oro_tax_rule_create')]
    #[Template('@OroTax/TaxRule/update.html.twig')]
    #[Acl(id: 'oro_tax_rule_create', type: 'entity', class: TaxRule::class, permission: 'CREATE')]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new TaxRule());
    }

    #[Route(path: '/update/{id}', name: 'oro_tax_rule_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_tax_rule_update', type: 'entity', class: TaxRule::class, permission: 'EDIT')]
    public function updateAction(TaxRule $taxRule): array|RedirectResponse
    {
        return $this->update($taxRule);
    }

    protected function update(TaxRule $taxRule): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $taxRule,
            $this->createForm(TaxRuleType::class, $taxRule),
            $this->container->get(TranslatorInterface::class)->trans('oro.tax.controller.taxrule.saved.message')
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
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

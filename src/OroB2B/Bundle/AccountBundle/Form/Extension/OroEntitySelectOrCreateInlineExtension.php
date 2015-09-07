<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class OroEntitySelectOrCreateInlineExtension extends AbstractTypeExtension
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        if ($this->isFrontend()) {
            $resolver->setDefault('grid_widget_route', 'orob2b_frontend_datagrid_widget');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // Search queries must be routed to frontend instead of backend when called from frontend
        if ($this->isFrontend() && isset($view->vars['configs']['route_name'])
            && $view->vars['configs']['route_name'] === 'oro_form_autocomplete_search'
        ) {
            $view->vars['configs']['route_name'] = 'orob2b_frontend_autocomplete_search';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OroEntitySelectOrCreateInlineType::NAME;
    }

    /**
     * @return bool
     */
    protected function isFrontend()
    {
        $token = $this->tokenStorage->getToken();

        return $token && $token->getUser() instanceof AccountUser;
    }
}

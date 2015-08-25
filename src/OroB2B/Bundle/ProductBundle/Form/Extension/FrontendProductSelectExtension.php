<?php

namespace OroB2B\Bundle\ProductBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

class FrontendProductSelectExtension extends AbstractTypeExtension
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
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof AccountUser) {
            $resolver->setDefault('grid_name', 'products-select-grid-frontend');
            $resolver->setDefault('autocomplete_alias', 'orob2b_frontend_products_list');
            $resolver->setDefault(
                'configs',
                [
                    'route_name' => 'orob2b_frontend_autocomplete_search',
                    'placeholder' => 'orob2b.product.form.choose',
                    'result_template_twig' => 'OroB2BProductBundle:Product:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroB2BProductBundle:Product:Autocomplete/selection.html.twig',
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductSelectType::NAME;
    }
}

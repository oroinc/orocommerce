<?php

namespace Oro\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;

class PageTemplateType extends AbstractType
{
    /** @var PageTemplatesManager */
    private $pageTemplatesManager;

    /**
     * @param PageTemplatesManager $pageTemplatesManager
     */
    public function __construct(PageTemplatesManager $pageTemplatesManager)
    {
        $this->pageTemplatesManager = $pageTemplatesManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['route_name'])
            ->setDefaults([
                'choices' => function (Options $options) {
                    return $this->getPageTemplatesByRouteName($options['route_name']);
                },
                'placeholder' => 'oro_frontend.system_configuration.fields.no_page_template.label',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @param string $routeName
     * @return array
     */
    private function getPageTemplatesByRouteName($routeName)
    {
        $routePageTemplates = $this->pageTemplatesManager->getRoutePageTemplates();
        if (array_key_exists($routeName, $routePageTemplates)) {
            return $routePageTemplates[$routeName]['choices'];
        }

        return [];
    }
}

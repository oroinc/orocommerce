<?php

namespace Oro\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;

class PageTemplateCollectionType extends AbstractType
{
    const NAME = 'oro_frontend_page_template_collection';

    /**
     * @var PageTemplatesManager
     */
    protected $pageTemplatesManager;

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->pageTemplatesManager->getRoutePageTemplates() as $routeName => $routeOptions) {
            $builder->add(
                $routeName,
                ChoiceType::class,
                [
                    'choices' => $routeOptions['choices'],
                    'placeholder' => 'oro_frontend.system_configuration.fields.no_page_template.label',
                    'label' => $routeOptions['label']
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}

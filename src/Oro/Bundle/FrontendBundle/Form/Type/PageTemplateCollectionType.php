<?php

namespace Oro\Bundle\FrontendBundle\Form\Type;

use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {

            $form = $event->getForm();

            foreach ($this->pageTemplatesManager->getRoutePageTemplates() as $routeName => $options) {
                $options['choices'] = array_merge(
                    [null => 'oro_frontend.system_configuration.fields.no_page_template.label'],
                    $options['choices']
                );
                $form->add(
                    $routeName,
                    ChoiceType::class,
                    [
                        'choices' => $options['choices'],
                        'label' => $options['label']
                    ]
                );
            }
        });
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

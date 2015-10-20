<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteScopedDataType extends AbstractType
{
    const NAME = 'orob2b_account_website_scoped_data_type';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @return Website[]
     */
    protected $websites;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'type',
            'skipChildren'
        ]);

        $resolver->setDefaults([
            //'csrf_protection'   => false,
            'preloaded_websites' => null,
            'options' => null
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $loadedWebsites = !empty($options['preloaded_websites'])
            ? $options['preloaded_websites']
            : $this->getWebsites();

        $options['options']['data'] = $options['data'];
        $options['options']['ownership_disabled'] = true;

        foreach ($loadedWebsites as $website) {
            $options['options']['website'] = $website;
            $builder->add(
                $website->getId(),
                $options['type'],
                $options['options']
            );
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $formOptions = $form->getConfig()->getOptions();

        $formOptions['options']['data'] = $form->getData();
        $formOptions['options']['ownership_disabled'] = true;

        foreach ($data as $websiteId => $value) {
            $formOptions['options']['website'] = $this->registry
                ->getManagerForClass('OroB2BWebsiteBundle:Website')
                ->getReference('OroB2BWebsiteBundle:Website', $websiteId);

            $form->add(
                $websiteId,
                $formOptions['type'],
                $formOptions['options']
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['websites'] = $this->getWebsites();
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['skipChildren']) {
            foreach ($view->children as $key => $value) {
                if (filter_var($key, FILTER_VALIDATE_INT)) {
                    unset($view->children[$key]);
                }
            }
        }
    }

    /**
     * @return Website[]
     */
    protected function getWebsites()
    {
        if (null === $this->websites) {
            $this->websites = $this->registry->getRepository('OroB2BWebsiteBundle:Website')->getAllWebsites();
        }

        return $this->websites;
    }
}

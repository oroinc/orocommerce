<?php

namespace Oro\Bundle\ScopeBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopedDataType extends AbstractType
{
    const NAME = 'oro_scoped_data_type';
    const SCOPE_OPTION = 'scope';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
        $resolver->setRequired(
            [
                'type',
            ]
        );

        $resolver->setDefaults(
            [
                'preloaded_scopes' => [],
                'options' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['preloaded_scopes'])) {
            $loadedWebsites = $options['preloaded_scopes'];
        } else {
            $loadedWebsites = $this->getScopes();
        }

        $options['options']['data'] = $options['data'];
        $options['options']['ownership_disabled'] = true;

        foreach ($loadedWebsites as $website) {
            $options['options'][self::SCOPE_OPTION] = $website;
            $builder->add(
                $website->getId(),
                $options['type'],
                $options['options']
            );
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
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

        if (!$data) {
            return;
        }
        foreach ($data as $websiteId => $value) {
            if ($form->has($websiteId)) {
                continue;
            }

            /** @var EntityManager $em */
            $em = $this->registry->getManagerForClass(Scope::class);

            $formOptions['options'][self::SCOPE_OPTION] = $em
                ->getReference(Scope::class, $websiteId);

            $form->add(
                $websiteId,
                $formOptions['type'],
                $formOptions['options']
            );
        }
    }

    /**
     * @param FormEvent $event
     * @throws \Doctrine\ORM\ORMException
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();

        $formOptions = $form->getConfig()->getOptions();

        $formOptions['options']['ownership_disabled'] = true;
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(Scope::class);

        foreach ($event->getData() as $scopeId => $value) {
            $formOptions['options']['data'] = [];

            if (is_array($value)) {
                $formOptions['options']['data'] = $value;
            }

            $formOptions['options'][self::SCOPE_OPTION] = $em->getReference(Scope::class, $scopeId);

            $form->add(
                $scopeId,
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
        $view->vars['scopes'] = $this->getScopes();
    }

    /**
     * @return Scope[]
     */
    protected function getScopes()
    {
//        todo: 4710 create scope provider, redefine in website bundle
//        if (null === $this->s) {
//            $this->websites = $this->websiteProvider->getWebsites();
//        }
//
//        return $this->websites;
        return [];
    }
}

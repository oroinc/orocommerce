<?php

namespace OroB2B\Bundle\WebsiteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteScopedDataType extends AbstractType
{
    const NAME = 'orob2b_website_scoped_data_type';
    const WEBSITE_OPTION = 'website';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @return Website[]
     */
    protected $websites;

    /**
     * @var string
     */
    protected $websiteCLass = 'OroB2B\Bundle\WebsiteBundle\Entity\Website';

    /**
     * @var WebsiteProviderInterface
     */
    protected $websiteProvider;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param ManagerRegistry $registry
     * @param WebsiteProviderInterface $websiteProvider
     */
    public function __construct(ManagerRegistry $registry, WebsiteProviderInterface $websiteProvider)
    {
        $this->registry = $registry;
        $this->websiteProvider = $websiteProvider;
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
                'preloaded_websites' => [],
                'options' => null,
            ]
        );
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
            $options['options'][self::WEBSITE_OPTION] = $website;
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
            $em = $this->registry->getManagerForClass($this->websiteCLass);

            $formOptions['options'][self::WEBSITE_OPTION] = $em
                ->getReference($this->websiteCLass, $websiteId);

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

        foreach ($event->getData() as $websiteId => $value) {
            /** @var EntityManager $em */
            $em = $this->registry->getManagerForClass($this->websiteCLass);
            $formOptions['options']['data'] = [];

            if (is_array($value)) {
                $formOptions['options']['data'] = $value;
            }

            $formOptions['options'][self::WEBSITE_OPTION] = $em->getReference($this->websiteCLass, $websiteId);

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
     * @return Website[]
     */
    protected function getWebsites()
    {
        if (null === $this->websites) {
            $this->websites = $this->websiteProvider->getWebsites();
        }

        return $this->websites;
    }

    /**
     * @param string $websiteCLass
     */
    public function setWebsiteClass($websiteCLass)
    {
        $this->websiteCLass = $websiteCLass;
    }
}

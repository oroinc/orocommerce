<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepositoryInterface;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListFallback;

class PriceListsSettingsType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_lists_settings';

    const PRICE_LIST_COLLECTION_FIELD = 'priceListCollection';
    const FALLBACK_FIELD = 'fallback';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @param ManagerRegistry $registry
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(ManagerRegistry $registry, PropertyAccessorInterface $propertyAccessor)
    {
        $this->registry = $registry;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::FALLBACK_FIELD,
            'choice',
            [
                'label' => 'orob2b.pricing.fallback.label',
                'mapped' => true,
                'choices' => $options['fallback_choices'],
            ]
        )
            ->add(
                self::PRICE_LIST_COLLECTION_FIELD,
                PriceListCollectionType::NAME,
                ['label' => 'orob2b.pricing.pricelist.entity_plural_label', 'mapped' => true]
            );
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'render_as_widget' => true,
                'label' => false,
            ]
        );
        $resolver->setRequired(
            [
                'fallback_class_name',
                'target_field_name',
                'fallback_choices',
                'website',
                'default_fallback',
            ]
        );
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var object|null $targetEntity */
        $targetEntity = $form->getRoot()->getData();
        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }
        $config = $form->getConfig();
        $fallback = $this->getFallback($config, $targetEntity);
        $fallbackField = $form->get(self::FALLBACK_FIELD);
        $defaultFallback = $config->getOption('default_fallback');
        if (!$fallback || !$fallback->getFallback()) {
            $fallbackField->setData($defaultFallback);
        } else {
            $fallbackField->setData($fallback->getFallback());
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }
        /** @var Account|null $account */
        $targetEntity = $form->getRoot()->getData();
        if (!$targetEntity) {
            return;
        }
        $config = $form->getConfig();
        $fallbackClassName = $config->getOption('fallback_class_name');
        $fallback = $this->getFallback($form->getConfig(), $targetEntity);
        if (!$fallback) {
            /** @var PriceListFallback $fallback */
            $fallback = new $fallbackClassName;
            $this->getEntityManager($fallbackClassName)->persist($fallback);
        }
        $this->propertyAccessor->setValue($fallback, $config->getOption('target_field_name'), $targetEntity);
        $fallback->setFallback($form->get(self::FALLBACK_FIELD)->getData());
        $fallback->setWebsite($config->getOption('website'));
    }

    /**
     * @param FormConfigInterface $config
     * @param object $targetEntity
     * @return object
     */
    protected function getFallback(FormConfigInterface $config, $targetEntity)
    {
        $fallbackClassName = $config->getOption('fallback_class_name');

        return $this->getRepository($fallbackClassName)
            ->findOneBy(
                [
                    $config->getOption('target_field_name') => $targetEntity,
                    'website' => $config->getOption('website'),
                ]
            );
    }

    /**
     * @param string $className
     * @return EntityManager
     */
    protected function getEntityManager($className)
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->registry->getManagerForClass($className);
        }

        return $this->entityManager;
    }

    /**
     * @param string $className
     * @return PriceListRepositoryInterface|EntityRepository
     */
    protected function getRepository($className)
    {
        if (!$this->repository) {
            $this->repository = $this->getEntityManager($className)->getRepository($className);
        }

        return $this->repository;
    }
}

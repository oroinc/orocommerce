<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListFallback;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;

class PriceListsSettingsType extends AbstractType
{
    const PRICE_LIST_COLLECTION_FIELD = 'priceListCollection';
    const FALLBACK_FIELD = 'fallback';
    const NAME = 'orob2b_pricing_price_lists_settings';

    /** @var  Registry */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }


    /**
     * @inheritDoc
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
        );

        $builder->add(
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
        $resolver->setRequired(['fallback_class_name', 'target_field_name', 'fallback_choices', 'website']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var object|null $account */
        $targetEntity = $form->getParent()->getParent()->getData();
        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }
        $config = $form->getConfig();
        $fallback = $this->getFallback(
            $config->getOption('fallback_class_name'),
            $targetEntity,
            $config->getOption('target_field_name'),
            $config->getOption('website')
        );
        $fallbackField = $form->get(self::FALLBACK_FIELD);
        if (!$fallback || $fallback->getFallback() === PriceListAccountFallback::ACCOUNT_GROUP) {
            $fallbackField->setData(PriceListAccountFallback::ACCOUNT_GROUP);
        } else {
            $fallbackField->setData(PriceListAccountFallback::CURRENT_ACCOUNT_ONLY);
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
        $targetEntity = $form->getParent()->getParent()->getData();
        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }
        $config = $form->getConfig();
        /** @var Website $website */
        $website = $config->getOption('website');
        $fallbackClassName = $config->getOption('fallback_class_name');
        $fallback = $this->getFallback(
            $fallbackClassName,
            $targetEntity,
            $config->getOption('target_field_name'),
            $config->getOption('website')
        );
        if (!$fallback) {
            /** @var PriceListFallback $fallback */
            $fallback = new $fallbackClassName;
            $this->registry->getManagerForClass($fallbackClassName)->persist($fallback);
        }
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($fallback, $config->getOption('target_field_name'), $targetEntity);
        $fallback->setFallback($form->get(self::FALLBACK_FIELD)->getData());
        $fallback->setWebsite($website);
    }

    /**
     * @param string $className
     * @param object $targetEntity
     * @param string $targetFieldName
     * @param Website $website
     * @return null|PriceListFallback
     */
    protected function getFallback($className, $targetEntity, $targetFieldName, Website $website)
    {
        /** @var PriceListFallback $fallback */
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className)
            ->findOneBy([$targetFieldName => $targetEntity, 'website' => $website]);
    }
}

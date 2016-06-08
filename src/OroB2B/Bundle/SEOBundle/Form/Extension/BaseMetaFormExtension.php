<?php

namespace OroB2B\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Persistence\ManagerRegistry;

abstract class BaseMetaFormExtension extends AbstractTypeExtension
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Return the name of the extend entity which will be used for determining field labels
     * @return string
     */
    abstract public function getMetaFieldLabelPrefix();

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'metaTitles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => $this->getMetaFieldLabelPrefix() . '.meta-title.label',
                    'required' => false,
                    'type' => 'text',
                ]
            )
            ->add(
                'metaDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => $this->getMetaFieldLabelPrefix() . '.meta-description.label',
                    'required' => false,
                    'type' => 'textarea',
                ]
            )
            ->add(
                'metaKeywords',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => $this->getMetaFieldLabelPrefix() . '.meta-keywords.label',
                    'required' => false,
                    'type' => 'textarea',
                ]
            );

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
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

        $entity = $event->getData();
        $entityManager = $this->registry->getManagerForClass('OroB2BFallbackBundle:LocalizedFallbackValue');

        $this->persistMetaFields($entityManager, $entity->getMetaTitles());
        $this->persistMetaFields($entityManager, $entity->getMetaDescriptions());
        $this->persistMetaFields($entityManager, $entity->getMetaKeywords());
    }

    /**
     * Loop through list of LocalizedFallbackValue objects for a meta information field
     *
     * @param OroEntityManager $entityManager
     * @param LocalizedFallbackValue[] $metaFields
     */
    private function persistMetaFields(OroEntityManager $entityManager, $metaFields = array())
    {
        foreach ($metaFields as $field) {
            $entityManager->persist($field);
        }
    }
}

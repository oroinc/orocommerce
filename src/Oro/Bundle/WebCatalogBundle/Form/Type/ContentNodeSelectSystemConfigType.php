<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Content node select from type that used in system configuration
 */
class ContentNodeSelectSystemConfigType extends AbstractType
{
    const NAME = 'oro_web_catalog_content_node_select_system_config';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            if ($data && !is_object($data)) {
                $repository = $this->doctrineHelper->getEntityRepository(ContentNode::class);
                $data = $repository->find($data);
                $event->setData($data);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $webCatalogId = $this->configManager->get('oro_web_catalog.web_catalog');
        $repository = $this->doctrineHelper->getEntityRepository(WebCatalog::class);
        $webCatalog = $repository->find($webCatalogId);

        $resolver->setDefaults([
            'web_catalog' => $webCatalog
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ContentNodeSelectType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}

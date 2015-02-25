<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;

class WebsiteCollectionType extends AbstractType
{
    const NAME = 'orob2b_attribute_website_collection';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @return Website[]
     */
    protected $websites;

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
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired([
            'type',
        ]);

        $resolver->setDefaults([
            'options'           => [],
            'fallback_type'     => AttributePropertyFallbackType::NAME,
            'enabled_fallbacks' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->getWebsites() as $website) {
            $builder->add(
                $website->getId(),
                FallbackValueType::NAME,
                [
                    'label'             => $website->getName(),
                    'type'              => $options['type'],
                    'options'           => $options['options'],
                    'fallback_type'     => $options['fallback_type'],
                    'enabled_fallbacks' => $options['enabled_fallbacks'],
                ]
            );
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $filledData = $this->fillDefaultData($data);
            $event->setData($filledData);
        });
    }

    /**
     * @return Website[]
     */
    protected function getWebsites()
    {
        if (null === $this->websites) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository('OroB2BWebsiteBundle:Website');

            $this->websites = $entityRepository->createQueryBuilder('website')
                ->addOrderBy('website.id', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->websites;
    }

    /**
     * @param mixed $data
     * @return array
     */
    public function fillDefaultData($data)
    {
        if (!$data) {
            $data = [];
        }

        foreach ($this->getWebsites() as $website) {
            $websiteId = $website->getId();
            if (!isset($data[$websiteId])) {
                $data[$websiteId] = new FallbackType(FallbackType::SYSTEM);
            }
        }

        return $data;
    }
}

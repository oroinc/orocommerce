<?php

namespace Oro\Bundle\FallbackBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebsiteCollectionType extends AbstractType
{
    const NAME = 'oro_fallback_website_collection';

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
    protected $websiteClass;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $websiteClass
     */
    public function setWebsiteClass($websiteClass)
    {
        $this->websiteClass = $websiteClass;
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'entry_type',
        ]);

        $resolver->setDefaults([
            'entry_options'           => [],
            'fallback_type'     => FallbackPropertyType::class,
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
                FallbackValueType::class,
                [
                    'label'             => $website->getName(),
                    'entry_type'        => $options['entry_type'],
                    'entry_options'     => $options['entry_options'],
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
            $entityRepository = $this->registry->getRepository($this->websiteClass);

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

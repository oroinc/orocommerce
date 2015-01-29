<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;

class LocaleCollectionType extends AbstractType
{
    const NAME = 'orob2b_attribute_locale_collection';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @return Locale[]
     */
    protected $locales;

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
        foreach ($this->getLocales() as $locale) {
            // calculate enabled fallbacks for the specific locale
            $enabledFallbacks = $options['enabled_fallbacks'];
            if ($locale->getParentLocale()) {
                $enabledFallbacks = array_merge($enabledFallbacks, [FallbackType::PARENT_LOCALE]);
            }

            $builder->add(
                $locale->getId(),
                FallbackValueType::NAME,
                [
                    'label'             => $locale->getCode(),
                    'type'              => $options['type'],
                    'options'           => $options['options'],
                    'fallback_type'     => $options['fallback_type'],
                    'enabled_fallbacks' => $enabledFallbacks,
                ]
            );
        }
    }

    /**
     * @return Locale[]
     */
    protected function getLocales()
    {
        if (null === $this->locales) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository('OroB2BWebsiteBundle:Locale');

            $this->locales = $entityRepository->createQueryBuilder('locale')
                ->leftJoin('locale.parentLocale', 'parentLocale')
                ->addOrderBy('locale.id', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->locales;
    }
}

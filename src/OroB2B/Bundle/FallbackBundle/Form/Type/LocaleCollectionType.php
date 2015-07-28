<?php

namespace OroB2B\Bundle\FallbackBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;

class LocaleCollectionType extends AbstractType
{
    const NAME = 'orob2b_fallback_locale_collection';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @return Locale[]
     */
    protected $locales;

    /**
     * @var string
     */
    protected $localeClass;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $localeClass
     */
    public function setLocaleClass($localeClass)
    {
        $this->localeClass = $localeClass;
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
            'fallback_type'     => FallbackPropertyType::NAME,
            'enabled_fallbacks' => [],
            'value_type'        => FallbackValueType::NAME
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
            $parentLocaleCode = null;
            if ($locale->getParentLocale()) {
                $enabledFallbacks = array_merge($enabledFallbacks, [FallbackType::PARENT_LOCALE]);
                $parentLocaleCode = $locale->getParentLocale()->getCode();
            }

            $builder->add(
                $locale->getId(),
                $options['value_type'],
                [
                    'label'                       => $locale->getCode(),
                    'type'                        => $options['type'],
                    'options'                     => $options['options'],
                    'fallback_type'               => $options['fallback_type'],
                    'fallback_type_parent_locale' => $parentLocaleCode,
                    'enabled_fallbacks'           => $enabledFallbacks,
                ]
            );
        }

        $locales = $this->getLocales();
        if ($locales) {
            // use any locale field to resolve default data
            $locale = $locales[0];
            $localeField = $builder->get($locale->getId());

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($localeField) {
                $data = $event->getData();
                $filledData = $this->fillDefaultData($data, $localeField);
                $event->setData($filledData);
            });
        }
    }

    /**
     * @return Locale[]
     */
    protected function getLocales()
    {
        if (null === $this->locales) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository($this->localeClass);

            $this->locales = $entityRepository->createQueryBuilder('locale')
                ->leftJoin('locale.parentLocale', 'parentLocale')
                ->addOrderBy('locale.id', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->locales;
    }

    /**
     * @param mixed $data
     * @param FormBuilderInterface $form
     * @return array
     */
    public function fillDefaultData($data, FormBuilderInterface $form)
    {
        if (!$data) {
            $data = [];
        }

        foreach ($this->getLocales() as $locale) {
            $localeId = $locale->getId();
            if (!array_key_exists($localeId, $data)) {
                if ($locale->getParentLocale()) {
                    $data[$localeId] = new FallbackType(FallbackType::PARENT_LOCALE);
                } else {
                    $data[$localeId] = new FallbackType(FallbackType::SYSTEM);
                }
                if ($form->hasOption('default_callback')) {
                    /** @var \Closure $defaultCallback */
                    $defaultCallback = $form->getOption('default_callback');
                    $data[$localeId] = $defaultCallback($data[$localeId]);
                }
            }
        }

        return $data;
    }
}

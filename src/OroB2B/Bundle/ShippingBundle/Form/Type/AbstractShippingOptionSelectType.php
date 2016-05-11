<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

abstract class AbstractShippingOptionSelectType extends AbstractType
{
    const NAME = '';

    /** @var EntityRepository */
    protected $repository;

    /** @var ConfigManager */
    protected $configManager;

    /** @var UnitLabelFormatter */
    protected $formatter;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var string */
    protected $configParameterName;

    /** @var string */
    protected $entityClass;

    /**
     * @param EntityRepository $repository
     * @param ConfigManager $configManager
     * @param UnitLabelFormatter $formatter
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityRepository $repository,
        ConfigManager $configManager,
        UnitLabelFormatter $formatter,
        TranslatorInterface $translator
    ) {
        $this->repository = $repository;
        $this->configManager = $configManager;
        $this->formatter = $formatter;
        $this->translator = $translator;
    }

    /**
     * @param string $configParameterName
     */
    public function setConfigParameterName($configParameterName)
    {
        $this->configParameterName = $configParameterName;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'setAcceptableUnits']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'validateUnits']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $formParent = $form->getParent();
        if (!$formParent) {
            return;
        }

        $view->vars['choices'] = [];

        $choices = $this->formatter->formatChoices($this->getUnits($options['full_list']), $options['compact']);
        foreach ($choices as $key => $value) {
            $view->vars['choices'][] = new ChoiceView($value, $key, $value);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function setAcceptableUnits(FormEvent $event)
    {
        $form = $event->getForm();
        $options = $form->getConfig()->getOptions();

        if ($options['choices_updated']) {
            return;
        }

        $formParent = $form->getParent();
        if (!$formParent) {
            return;
        }

        $options['choices'] = $this->getUnits($options['full_list']);
        $options['choices_updated'] = true;

        $formParent->add($form->getName(), $this->getName(), $options);
    }

    /**
     * @param FormEvent $event
     */
    public function validateUnits(FormEvent $event)
    {
        $form = $event->getForm();
        $units = new ArrayCollection($this->getUnits(true));
        $data = $this->repository->findBy(['code' => $event->getData()]);

        foreach ($data as $unit) {
            if (!$units->contains($unit)) {
                $form->addError(
                    new FormError(
                        $this->translator->trans(
                            'orob2b.shipping.validators.shipping_options.invalid',
                            [],
                            'validators'
                        )
                    )
                );
                break;
            }
        }
    }

    /**
     * @param bool $fullList
     *
     * @return MeasureUnitInterface[]|array
     */
    protected function getUnits($fullList = false)
    {
        $units = $this->repository->findAll();

        if (!$fullList) {
            $configCodes = $this->configManager->get($this->configParameterName);
            $units = array_filter(
                $units,
                function (MeasureUnitInterface $item) use ($configCodes) {
                    return in_array($item->getCode(), $configCodes, true);
                }
            );
        }

        return $units;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => $this->entityClass,
                'property' => 'code',
                'compact' => false,
                'full_list' => false,
                'choices_updated' => false
            ]
        )
        ->setAllowedTypes('compact', ['bool'])
        ->setAllowedTypes('full_list', ['bool'])
        ->setAllowedTypes('choices_updated', ['bool']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }
}

<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    /** @var string */
    protected $configParameterName;

    /**
     * @param EntityRepository $repository
     * @param ConfigManager $configManager
     * @param UnitLabelFormatter $formatter
     */
    public function __construct(
        EntityRepository $repository,
        ConfigManager $configManager,
        UnitLabelFormatter $formatter
    ) {
        $this->repository = $repository;
        $this->configManager = $configManager;
        $this->formatter = $formatter;
    }

    /**
     * @param string $configParameterName
     */
    public function setConfigParameterName($configParameterName)
    {
        $this->configParameterName = $configParameterName;
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
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => function (Options $options) {
                    if ($options['full_list']) {
                        $codes = array_map(
                            function (MeasureUnitInterface $entity) {
                                return $entity->getCode();
                            },
                            $this->repository->findAll()
                        );
                    } else {
                        $codes = $this->configManager->get($this->configParameterName);
                    }

                    $codes = array_merge($codes, $options['additional_codes']);

                    return $this->formatChoices($codes, $options['compact']);
                },
                'compact' => false,
                'additional_codes' => [],
                'full_list' => false
            ]
        );
        $resolver->setAllowedTypes('compact', ['bool'])
            ->setAllowedTypes('additional_codes', ['array'])
            ->setAllowedTypes('full_list', ['bool']);
    }

    /**
     * @param array $codes
     * @param boolean $isShort
     * @return array
     */
    protected function formatChoices(array $codes, $isShort)
    {
        $codes = array_combine($codes, $codes);
        $codes = array_map(
            function ($code) use ($isShort) {
                return $this->formatter->format($code, $isShort);
            },
            $codes
        );

        ksort($codes);

        return $codes;
    }
}

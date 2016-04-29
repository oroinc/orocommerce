<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

abstract class AbstractShippingOptionSelectType extends AbstractType
{
    const NAME = '';

    /** @var EntityRepository */
    protected $repository;

    /** @var UnitLabelFormatter */
    protected $formatter;

    /**
     * @param EntityRepository $repository
     * @param UnitLabelFormatter $formatter
     */
    public function __construct(EntityRepository $repository, UnitLabelFormatter $formatter)
    {
        $this->repository = $repository;
        $this->formatter = $formatter;
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
                'choices' => $this->getChoices()
            ]
        );
    }

    /**
     * @return array
     */
    protected function getChoices()
    {
        /** @var MeasureUnitInterface[] $entities */
        $entities = $this->repository->findAll();
        $choices = [];

        foreach ($entities as $entity) {
            $choices[$entity->getCode()] = $this->formatter->format($entity->getCode());
        }

        return $choices;
    }
}

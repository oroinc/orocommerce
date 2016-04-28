<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\SaleBundle\Manager\QuoteDemandManager;

class QuoteDemandType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_demand';

    /**
     * @var QuoteDemandManager
     */
    protected $quoteDemandManager;

    /**
     * @param QuoteDemandManager $quoteDemandManager
     */
    public function __construct(QuoteDemandManager $quoteDemandManager)
    {
        $this->quoteDemandManager = $quoteDemandManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data']);
        $resolver->setDefault('data_class', 'OroB2B\Bundle\SaleBundle\Entity\QuoteDemand');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var QuoteDemand $quoteDemand */
        $quoteDemand = $options['data'];
        $builder->add(
            'demandProducts',
            QuoteProductDemandCollectionType::NAME,
            [
                'data' => $quoteDemand->getDemandProducts()
            ]
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if ($data instanceof QuoteDemand) {
            $this->quoteDemandManager->recalculateSubtotals($data);
        }
    }
}

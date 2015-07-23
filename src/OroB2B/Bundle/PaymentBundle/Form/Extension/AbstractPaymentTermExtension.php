<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\Translator;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;

abstract class AbstractPaymentTermExtension extends AbstractTypeExtension
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /** @var  Translator */
    protected $translator;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry, Translator $translator)
    {
        $this->registry = $registry;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'paymentTerm',
            PaymentTermSelectType::NAME,
            [
                'label' => 'orob2b.payment.paymentterm.entity_label',
                'required' => false,
                'mapped' => false,
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * @param FormEvent $event
     */
    abstract public function onPostSetData(FormEvent $event);

    /**
     * @param FormEvent $event
     */
    abstract public function onPostSubmit(FormEvent $event);

    /**
     * @return PaymentTermRepository
     */
    protected function getPaymentTermRepository()
    {
        return $this->registry->getManagerForClass('OroB2BPaymentBundle:PaymentTerm')
            ->getRepository('OroB2BPaymentBundle:PaymentTerm');
    }
}

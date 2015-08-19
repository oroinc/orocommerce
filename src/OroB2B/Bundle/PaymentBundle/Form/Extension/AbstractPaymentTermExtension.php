<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;

abstract class AbstractPaymentTermExtension extends AbstractTypeExtension
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     * @param string $paymentTermClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, TranslatorInterface $translator, $paymentTermClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
        $this->paymentTermClass = $paymentTermClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options = array_replace_recursive([
            'paymentTermOptions' => [
                'label'    => 'orob2b.payment.paymentterm.entity_label',
                'required' => false,
                'mapped'   => false,
            ]
        ], $options);

        $builder->add(
            'paymentTerm',
            PaymentTermSelectType::NAME,
            $options['paymentTermOptions']
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
        return $this->doctrineHelper->getEntityRepository($this->paymentTermClass);
    }
}

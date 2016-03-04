<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;

class OrderLineItemTypeExtension extends AbstractTypeExtension
{
    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /** @var TaxManager */
    protected $taxManager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $entityClass;

    /** @var \Twig_Environment  */
    protected $twig;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxManager $taxManager
     * @param \Twig_Environment $twig
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxManager $taxManager,
        \Twig_Environment $twig
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxManager = $taxManager;
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderLineItemType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $builder
            ->add(
                'taxes',
                null,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.order.orderlineitem.taxes.label'
                ]
            )
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $entity = $event->getForm()->getData();

        if (empty($entity)) {
            return;
        }

        $result = $this->taxManager->getTax($entity);
        if (!$result->count()) {
            return;
        }

        $template = $this->twig->render(
            'OroB2BTaxBundle:Order:order_line_item_taxes.html.twig',
            ['result' => $result]
        );

        $event->getForm()->get('taxes')->setData($template);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'sections' => [
                    'taxes' => ['data' => ['taxes' => []], 'order' => 50],
                ],
            ]
        );
    }
}

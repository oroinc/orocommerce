<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;

abstract class AbstractTaxExtension extends AbstractTypeExtension
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $taxCodeClass;

    /** @var EntityRepository */
    protected $repository;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $taxCodeClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, $taxCodeClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxCodeClass = $taxCodeClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addTaxCodeField($builder);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    abstract protected function addTaxCodeField(FormBuilderInterface $builder);

    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        $entity = $event->getData();
        if (!$entity || !$this->doctrineHelper->getEntityIdentifier($entity)) {
            return;
        }

        $taxCode = $this->getTaxCode($entity);

        $event->getForm()->get('taxCode')->setData($taxCode);
    }

    /**
     * @param object $object
     * @return AbstractTaxCode|null
     */
    abstract protected function getTaxCode($object);

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->doctrineHelper->getEntityRepository($this->taxCodeClass);
        }

        return $this->repository;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        $entity = $event->getData();
        if (!$entity) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        $taxCodeNew = $form->get('taxCode')->getData();
        $taxCode = $this->getTaxCode($entity);

        $this->handleTaxCode($entity, $taxCode, $taxCodeNew);
    }

    /**
     * @param object $entity
     * @param AbstractTaxCode $taxCode
     * @param AbstractTaxCode $taxCodeNew
     */
    abstract protected function handleTaxCode(
        $entity,
        AbstractTaxCode $taxCode = null,
        AbstractTaxCode $taxCodeNew = null
    );
}

<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class FrontendAccountSelectType extends AbstractType
{
    const NAME = 'oro_customer_frontend_account_select';

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => 'OroCustomerBundle:Account',


            ]
        );
        $resolver->setRequired(['addresses']);
        $resolver->setNormalizer(
            'query_builder',
            function (Options $options) {
                return function (AccountRepository $repository) use ($options) {
                    $qb =  $repository->createQueryBuilder('a');
                    $qb
                        ->where($qb->expr()->eq('a.id', ':test'))
                        ->setParameter('test', $options['addresses'])
                        ->orderBy('a.name', 'DESC');
                    $a2 = $this->aclHelper->apply($qb);
                    return $qb;
                };
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_translatable_entity';
    }
}

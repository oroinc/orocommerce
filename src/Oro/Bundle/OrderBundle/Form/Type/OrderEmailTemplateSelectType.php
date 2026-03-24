<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateOrganizationProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting email template associated with Order entity.
 */
final class OrderEmailTemplateSelectType extends AbstractType
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EmailTemplateOrganizationProvider $organizationProvider
    ) {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        /** @var EmailTemplateRepository $repository */
        $repository = $this->doctrine->getRepository(EmailTemplate::class);
        $qb = $repository->getEntityTemplatesQueryBuilder(
            Order::class,
            $this->organizationProvider->getOrganization()
        );
        $resolver->setDefaults([
            'query_builder' => $qb,
            'class' => EmailTemplate::class,
            'choice_label' => 'name',
            'choice_value' => 'name',
        ]);
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($name) {
                    return $this->doctrine->getRepository(EmailTemplate::class)
                        ->findBy(['name' => $name, 'entityName' => Order::class]);
                },
                function ($emailTemplate) {
                    if (\is_null($emailTemplate)) {
                        return '';
                    }
                    return $emailTemplate->getName();
                }
            )
        );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_order_order_email_template_select';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2TranslatableEntityType::class;
    }
}

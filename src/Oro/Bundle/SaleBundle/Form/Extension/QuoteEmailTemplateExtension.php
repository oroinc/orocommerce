<?php

namespace Oro\Bundle\SaleBundle\Form\Extension;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateOrganizationProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Excludes the "quote_email_link_guest" from list of available templates on "send email" form.
 */
class QuoteEmailTemplateExtension extends AbstractTypeExtension
{
    public function __construct(
        private EmailTemplateOrganizationProvider $organizationProvider,
        private FeatureChecker $featureChecker
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (!$data instanceof Email || $data->getEntityClass() !== Quote::class) {
                    return;
                }

                FormUtils::replaceField(
                    $event->getForm(),
                    'template',
                    [
                        'selectedEntity' => Quote::class,
                        'choice_label' => function (EmailTemplate $emailTemplate) {
                            $website = $emailTemplate->getWebsite();
                            if ($website !== null) {
                                return sprintf('%s (%s)', $emailTemplate->getName(), $website->getName());
                            }

                            return $emailTemplate->getName();
                        },
                        'query_builder' => function (EmailTemplateRepository $templateRepository) {
                            $excludeNames = [];
                            if (!$this->featureChecker->isFeatureEnabled('guest_quote')) {
                                $excludeNames[] = 'quote_email_link_guest';
                            }

                            return $templateRepository->getEntityTemplatesQueryBuilder(
                                Quote::class,
                                $this->organizationProvider->getOrganization(),
                                false,
                                false,
                                true,
                                $excludeNames
                            );
                        },
                    ],
                    ['choice_list', 'choices']
                );
            }
        );
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [EmailType::class];
    }
}

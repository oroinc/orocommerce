<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Load new templates if not present, update existing as configured by emailsUpdateConfig.
 */
class LoadEmailTemplates extends AbstractEmailFixture implements VersionedFixtureInterface
{
    /**
     * To update template without overriding customized content add it's name as key and add expected previous
     * content MD5 to array of hashes.
     * To force update replace content hashes array with true.
     *
     * [
     *     <template_name> => [<MD5_of_previous_version_allowed_to_update>],
     *     <template_name_2> => true
     * ]
     */
    private array $emailsUpdateConfig = [
        'checkout_registration_confirmation' => ['61d0aa78a03cff496e373d85f3f3bfce'],
        'checkout_customer_user_reset_password' => ['d5eac9230ad16940519a2cd1e0bfa88e'],
    ];

    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        return '1.0';
    }

    /**
     * {@inheritDoc}
     */
    protected function findExistingTemplate(ObjectManager $manager, array $template): ?EmailTemplate
    {
        if (empty($template['params']['name'])) {
            return null;
        }

        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $template['params']['name'],
            'entityName' => $template['params']['entityName']
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template): void
    {
        foreach ($this->emailsUpdateConfig as $templateName => $contentHashes) {
            if ($emailTemplate->getName() === $templateName
                && ($contentHashes === true || \in_array(md5($emailTemplate->getContent()), $contentHashes, true))
            ) {
                parent::updateExistingTemplate($emailTemplate, $template);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCheckoutBundle/Migrations/Data/ORM/data/emails/order');
    }
}

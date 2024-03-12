<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Added html tag around twig tags
 * Allows to edit text from WYSIWYG editor and does not break the twig template
 */
class ConvertOrderConfirmationEmail extends AbstractEmailFixture
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [UpdateOrderConfirmationEmailTemplate::class];
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

    /**
     * {@inheritDoc}
     */
    protected function loadTemplate(ObjectManager $manager, $fileName, array $file): void
    {
        if ($fileName !== 'order_confirmation') {
            return;
        }

        $template = file_get_contents($file['path']);
        $templateContent = EmailTemplate::parseContent($template);
        $existingEmailTemplatesList = $this->getEmailTemplatesList($this->getPreviousEmailsDir());
        $existingTemplate = file_get_contents($existingEmailTemplatesList[$fileName]['path']);
        $existingParsedTemplate = EmailTemplate::parseContent($existingTemplate);
        $existingEmailTemplate = $this->findExistingTemplate($manager, $existingParsedTemplate);

        if ($existingEmailTemplate) {
            $this->updateExistingTemplate($existingEmailTemplate, $templateContent);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template): void
    {
        $emailTemplate->setContent($template['content']);
    }

    /**
     * {@inheritDoc}
     */
    protected function findExistingTemplate(ObjectManager $manager, array $template): ?EmailTemplate
    {
        if (!isset($template['params']['name'])
            || !isset($template['content'])
        ) {
            return null;
        }

        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $template['params']['name'],
            'entityName' => Order::class,
            'content' => $template['content']
        ]);
    }

    private function getPreviousEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCheckoutBundle/Migrations/Data/ORM/data/emails/v1_1');
    }
}

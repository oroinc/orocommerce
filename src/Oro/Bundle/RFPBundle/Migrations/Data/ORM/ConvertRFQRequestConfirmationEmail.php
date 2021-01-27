<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * Added html tag around twig tags
 * Allows to edit text from WYSIWYG editor and does not break the twig template
 */
class ConvertRFQRequestConfirmationEmail extends AbstractEmailFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroRFPBundle/Migrations/Data/ORM/data/emails/request');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadEmailTemplates::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTemplate(ObjectManager $manager, $fileName, array $file)
    {
        if ($fileName !== 'confirmation') {
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
     * @inheritdoc
     */
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template)
    {
        $emailTemplate->setContent($template['content']);
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingTemplate(ObjectManager $manager, array $template)
    {
        if (!isset($template['params']['name'])
            || !isset($template['content'])
        ) {
            return null;
        }
        return $manager->getRepository('OroEmailBundle:EmailTemplate')->findOneBy([
            'name' => $template['params']['name'],
            'entityName' => Request::class,
            'content' => $template['content']
        ]);
    }

    /**
     * Return path to old email templates
     *
     * @return string
     */
    private function getPreviousEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroRFPBundle/Migrations/Data/ORM/data/emails/v1_0');
    }
}

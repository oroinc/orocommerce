<?php

namespace OroB2B\Bundle\SaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\SaleBundle\Entity\Quote;

class QuoteNotificationController extends Controller
{
    /**
     * @Route("{id}/email", name="quote_notification_email")
     * @AclAncestor("oro_email_email_create")
     * @Template("OroB2BSaleBundle:QuoteNotification/dialog:update.html.twig")
     * @param Quote $quote
     * @return array
     */
    public function emailAction(Quote $quote)
    {
        $emailModel = $this->get('oro_email.email.model.builder')->createEmailModel();
        $toEmailAddress = $this->get('oro_email.email_holder_helper')->getEmail($quote);
        $emailModel->setTo([$toEmailAddress]);
        $emailModel->setContexts([$quote]);
        $emailModel->setEntityClass(ClassUtils::getClass($quote));
        $emailModel->setEntityId($quote->getId());
        $em = $this
            ->getDoctrine()
            ->getManagerForClass('OroEmailBundle:EmailTemplate');

        $emailTemplate = $em
            ->getRepository('OroEmailBundle:EmailTemplate')
            ->findByName('customer_notification');
        $emailModel->setTemplate($emailTemplate);

        $responseData = [
            'entity' => $emailModel,
            'saved' => false,
            'appendSignature' => (bool)$this->get('oro_config.user')->get('oro_email.append_signature'),
            'quote' => $quote
        ];
        if ($this->get('oro_email.form.handler.email')->process($emailModel)) {
            $responseData['saved'] = true;
            // todo $quote->setLocked(); $em->flush(); after BB-997 closed
        }
        $responseData['form'] = $this->get('oro_email.form.email')->createView();

        return $responseData;
    }
}

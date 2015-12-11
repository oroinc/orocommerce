<?php
/**
 * Created by PhpStorm.
 * User: g
 * Date: 09.12.15
 * Time: 17:15
 */

namespace OroB2B\Bundle\TaxBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;

class FormViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $productId = (int)$request->get('id');
        if (!$productId) {
            return;
        }

        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference('OroB2BProductBundle:Product', $productId);
        if (!$product) {
            return;
        }

        /** @var ProductTaxCodeRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroB2BTaxBundle:ProductTaxCode');
        $entity = $repository->findOneByProduct($product);

        $template = $event->getEnvironment()->render(
            'OroB2BTaxBundle:Product:tax_code_view.html.twig',
            ['entity' => $entity]
        );
        $this->addTaxCodeBlock($event->getScrollData(), $template, 'orob2b.tax.product.section.taxes');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BTaxBundle:Product:tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addTaxCodeBlock($event->getScrollData(), $template, 'orob2b.tax.product.section.taxes');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $accountId = (int)$request->get('id');
        if (!$accountId) {
            return;
        }

        /** @var Account $account */
        $account = $this->doctrineHelper->getEntityReference('OroB2BAccountBundle:Account', $accountId);
        if (!$account) {
            return;
        }

        /** @var AccountTaxCodeRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroB2BTaxBundle:AccountTaxCode');
        $entity = $repository->findOneByAccount($account);

        $template = $event->getEnvironment()->render(
            'OroB2BTaxBundle:Account:tax_code_view.html.twig',
            ['entity' => $entity]
        );
        $this->addTaxCodeBlock($event->getScrollData(), $template, 'orob2b.tax.account.section.taxes');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BTaxBundle:Account:tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addTaxCodeBlock($event->getScrollData(), $template, 'orob2b.tax.account.section.taxes');
    }

    /**
     * @param ScrollData $scrollData
     * @param string     $html
     * @param string     $title
     */
    protected function addTaxCodeBlock(ScrollData $scrollData, $html, $title)
    {
        $blockLabel = $this->translator->trans($title);
        $blockId    = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}

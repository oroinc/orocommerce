<?php

namespace Oro\Bundle\CMSBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form handler for ContentTemplate form.
 */
class ContentTemplateHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    public const ALIAS = 'content_template_handler';

    private TagManager $tagManager;

    private ManagerRegistry $managerRegistry;

    public function __construct(TagManager $tagManager, ManagerRegistry $managerRegistry)
    {
        $this->tagManager = $tagManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param ContentTemplate $data
     * @throw \InvalidArgumentException
     */
    public function process($data, FormInterface $form, Request $request): bool
    {
        if (!$data instanceof ContentTemplate) {
            throw new \InvalidArgumentException(
                sprintf('Argument $data was expected to be an instance of %s', ContentTemplate::class)
            );
        }

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $this->submitPostPutRequest($form, $request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->managerRegistry->getManagerForClass(ContentTemplate::class);
                $em->persist($data);
                $em->flush();

                $tagArray = $form->get('tags')->getData();
                if ($tagArray instanceof ArrayCollection) {
                    $this->setTagsForContentTemplate($data, $tagArray);
                }

                return true;
            }
        }

        return false;
    }

    protected function setTagsForContentTemplate(ContentTemplate $contentTemplate, ArrayCollection $tags): void
    {
        $this->tagManager->setTags($contentTemplate, $tags);
        $this->tagManager->saveTagging($contentTemplate);
    }
}

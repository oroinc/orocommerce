<?php

namespace OroB2B\Bundle\RFPBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\NoteBundle\Entity\Note;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;

class RequestChangeStatusHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param EngineInterface $templating
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        EngineInterface $templating
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->templating = $templating;
    }

    /**
     * Process form
     *
     * @param RFPRequest $rfpRequest
     * @return bool True on successful processing, false otherwise
     */
    public function process(RFPRequest $rfpRequest)
    {
        if ($this->request->isMethod('POST')) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($rfpRequest);

                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param RFPRequest $rfpRequest
     */
    protected function onSuccess(RFPRequest $rfpRequest)
    {
        $status = $this->form->get('status')->getData();
        $noteMessage = trim($this->form->get('note')->getData());

        $rfpRequest->setStatus($status);

        if (!empty($noteMessage)) {
            $note = new Note();
            $note
                ->setTarget($rfpRequest)
                ->setMessage(
                    htmlspecialchars_decode(
                        $this->templating->render(
                            'OroB2BRFPBundle:Request:note.html.twig',
                            [
                                'status' => $status->getLabel(),
                                'note' => $noteMessage,
                            ]
                        )
                    )
                );

            $this->manager->persist($note);
        }

        $this->manager->flush();
    }
}

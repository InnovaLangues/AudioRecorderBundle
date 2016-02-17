<?php

namespace Innova\AudioRecorderBundle\EventListener\Resource;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Event\OpenResourceEvent;
use Claroline\CoreBundle\Event\CreateFormResourceEvent;
use Claroline\CoreBundle\Event\CreateResourceEvent;
use Innova\AudioRecorderBundle\Manager\AudioRecorderManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 *  @DI\Service()
 */
class AudioRecorderListener
{
    private $container;
    private $arm;

    /**
     * @DI\InjectParams({
     *      "container" = @DI\Inject("service_container"),
     *      "arm" = @DI\Inject("innova.audio_recorder.manager")
     * })
     */
    public function __construct(ContainerInterface $container, AudioRecorderManager $arm)
    {
        $this->container = $container;
        $this->arm = $arm;
    }

    /**
     * @DI\Observe("open_innova_audio_recorder")
     * Fired when a ResourceNode of type AudioFile is opened
     *
     * @param \Claroline\CoreBundle\Event\OpenResourceEvent $event
     *
     * @throws \Exception
     */
    public function onOpen(OpenResourceEvent $event)
    {
        $resource = $event->getResource();
        $route = $this->container
                ->get('router')
                ->generate('claro_resource_open', array(
            'node' => $resource->getResourceNode()->getId(),
            'resourceType' => 'file',
                )
        );
        $event->setResponse(new RedirectResponse($route));
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("create_innova_audio_recorder")
     *
     * @param CreateResourceEvent $event
     */
    public function onCreate(CreateResourceEvent $event)
    {
        $request = $this->container->get('request');


        $formData = $request->request->all();
        
        $blob = $request->files->get('file');
        //$parent = $event->getParent();
        $workspace = $event->getParent()->getWorkspace();
        //$workspaceDir = $this->workspaceManager->getStorageDirectory($workspace);
        $file = $this->arm->uploadFileAndCreateResource($formData, $blob, $workspace);
        $event->setPublished(true);
        $event->setResourceType('file');
        $event->setResources(array($file));
        $event->stopPropagation();
        

        //$errors = $this->arm->handleResourceCreation($formData, $blob);
        // Create form POPUP
        /*$content = $this->container->get('templating')->render(
          'InnovaAudioRecorderBundle:AudioRecorder:form.html.twig'
        );
        $event->setResponseContent($content);
        $event->stopPropagation();*/
    }

    /**
     * @DI\Observe("create_form_innova_audio_recorder")
     *
     * @param CreateFormResourceEvent $event
     */
    public function onCreateForm(CreateFormResourceEvent $event)
    {
        // Create form POPUP
        $content = $this->container->get('templating')->render(
          'InnovaAudioRecorderBundle:AudioRecorder:form.html.twig', array('resourceType' => 'innova_audio_recorder')
        );
        $event->setResponseContent($content);
        $event->stopPropagation();
    }
}

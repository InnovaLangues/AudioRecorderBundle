<?php

namespace Innova\AudioRecorderBundle\EventListener\Resource;

use JMS\DiExtraBundle\Annotation as DI;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Claroline\CoreBundle\Event\OpenResourceEvent;
use Claroline\CoreBundle\Event\CreateFormResourceEvent;

/**
 *  @DI\Service()
 */
class AudioRecorderListener
{

    
    private $container;

    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container")
     * })
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * @DI\Observe("open_innova_audio_recorder")
     * Fired when a ResourceNode of type AudioFile is opened
     * @param \Claroline\CoreBundle\Event\OpenResourceEvent $event
     * @throws \Exception
     */
    public function onOpen(OpenResourceEvent $event)
    {
        $resource = $event->getResource();
        $route = $this->container
                ->get('router')
                ->generate('claro_resource_open', array(
            'parentId' => $resource->getResourceNode()->getId(),
            'resourceType' => 'file'
                )
        );
        
        //'resourceType': _resource..getResourceType().getName()
        $event->setResponse(new RedirectResponse($route));
        $event->stopPropagation();
    }  
    
    /**
     * @DI\Observe("create_form_innova_audio_recorder")
     * @param CreateFormResourceEvent $event
     */
    public function onCreateForm(CreateFormResourceEvent $event)
    {
        // Create form
        $content = $this->container->get('templating')->render('InnovaAudioRecorderBundle:AudioRecorder:create.html.twig');
        $event->setResponseContent($content);
        $event->stopPropagation();
        
        /*$route = $this->container
                ->get('router')
                ->generate('innova_audio_file_create_form');
        
        //'resourceType': _resource.getPrimaryResource().getResourceType().getName()
        $event->setResponse(new RedirectResponse($route));
        $event->stopPropagation();*/
    }
}

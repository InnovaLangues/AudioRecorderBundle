<?php

namespace Innova\AudioRecorderBundle\Controller;


use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\View;
// Post Route Definition
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Entity\Resource\ResourceType;
use Claroline\CoreBundle\Manager\ResourceManager;
use Innova\AudioRecorderBundle\Manager\AudioRecorderManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Claroline\CoreBundle\Entity\Resource\File;
use Symfony\Component\Filesystem\Filesystem;
use JMS\DiExtraBundle\Annotation as DI;

/**
 *
 */
class AudioRecorderController
{

    protected $arm;

    /**
     * @DI\InjectParams({
     *      "arm"         = @DI\Inject("innova.audio_recorder.manager")
     * })
     */
    public function __construct(AudioRecorderManager $arm)
    {
        $this->arm = $arm;
    }

    /**
     * @Route("/add", name="innova_audio_recorder_submit", options={"expose" = true})
     * @Method("POST")
     */
    public function submitFormAction(Request $request)
    {
        $formData = $request->request->all();
        $blob = $request->files->get('file');

        $errors = $this->arm->handleResourceCreation($formData, $blob);

        if(count($errors) > 0){
          return new JsonResponse($errors, 500);
        }

        return new JsonResponse('success', 200);
    }


}

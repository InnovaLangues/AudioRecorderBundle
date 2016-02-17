<?php

namespace Innova\AudioRecorderBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Innova\AudioRecorderBundle\Manager\AudioRecorderManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\DiExtraBundle\Annotation as DI;

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
     * Not used anymore
     * uses FosRestBundle to automatically generate route
     * php app/console router:debug post_audio_recorder_blob for routing informations
     */
    public function postAudioRecorderBlobAction(Request $request)
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

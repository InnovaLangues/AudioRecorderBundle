<?php

namespace Innova\AudioRecorderBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * 
 *
 * 
 *
 */
class AudioRecorderController extends Controller {

    
    
    /**
     * @Route("/new", name="innova_audio_recorder_create_form")
     * @Method("GET")
     * 
     * @param type $request
     */
    public function createFormAction(Request $request){
        // create a new claro file from request...
        die('yep');
    }

}

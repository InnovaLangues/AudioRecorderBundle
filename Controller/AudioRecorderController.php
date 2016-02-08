<?php

namespace Innova\AudioRecorderBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Claroline\CoreBundle\Manager\FileManager;
use Claroline\CoreBundle\Entity\Resource\ResourceType;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Manager\WorkspaceManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Claroline\CoreBundle\Entity\Resource\File;

use JMS\DiExtraBundle\Annotation as DI;

/**
 *
 */
class AudioRecorderController extends Controller {

  protected $fileManager;
  protected $workspaceManager;
  protected $rm;
  protected $om;
  protected $fileDir;
  protected $uploadDir;

  /**
  * @DI\InjectParams({
  *      "fm"         = @DI\Inject("claroline.manager.file_manager"),
  *      "om"         = @DI\Inject("claroline.persistence.object_manager"),
  *      "rm"         = @DI\Inject("claroline.manager.resource_manager"),
  *      "wm"         = @DI\Inject("claroline.manager.workspace_manager"),
  *      "fileDir"    = @DI\Inject("%claroline.param.files_directory%"),
  *      "uploadDir"  = @DI\Inject("%claroline.param.uploads_directory%"),
  * })
  */
  public function __construct(FileManager $fm, WorkspaceManager $wm, ResourceManager $rm, ObjectManager $om, $fileDir, $uploadDir)
  {
      $this->fileManager = $fm;
      $this->workspaceManager = $wm;
      $this->rm = $rm;
      $this->fileDir = $fileDir;
      $this->uploadDir = $uploadDir;
      $this->om = $om;
      $this->rm = $rm;
  }


    /**
     * @Route("/add", name="innova_audio_recorder_submit", options={"expose" = true})
     * @Method("POST")
     * @EXT\ParamConverter("user", converter="current_user")
     */
    public function submitFormAction(User $user, Request $request){

        $formData = $request->request->all();
        $uploaded = $this->getRequest()->files->get('blob');
        $size = $uploaded->getSize();

        $isFirefox = $formData['nav'] === 'firefox';
        $fileName = $formData['filename'];
        $ext =  $isFirefox ? 'ogg':'wav';

        $name = $fileName . '.' . $ext;        
        $workspace = $user->getPersonalWorkspace();
        $hashName = 'WORKSPACE_' . $workspace->getId() .
                DIRECTORY_SEPARATOR .
                $this->container->get('claroline.utilities.misc')->generateGuid() .
                "." .
                $ext;
        
        $rt = $this->rm->getResourceTypeByName('file');
        if(!$rt){
            $rt = new ResourceType();
            $rt->setName('file');
        }          
       
        $file = new File();
        $file->setSize($size);
        $file->setName($name);
        $file->setHashName($hashName);
        $file->setMimeType('audio/' .$ext);
        
        // get personnal directory
        $parent = $this->rm->getWorkspaceRoot($workspace);
        // the name of the resource is blob... thats a pbm...
        $resource = $this->rm->create($file, $rt, $user, $workspace, $parent);   
        $this->fileManager->uploadContent($resource, $uploaded);

        return new JsonResponse(null, 200);

    }

}

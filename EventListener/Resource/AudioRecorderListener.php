<?php

namespace Innova\AudioRecorderBundle\EventListener\Resource;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Claroline\CoreBundle\Event\OpenResourceEvent;
use Claroline\CoreBundle\Event\CreateFormResourceEvent;
use Claroline\CoreBundle\Event\CreateResourceEvent;
use Claroline\CoreBundle\Form\FileType;
use Claroline\CoreBundle\Entity\Resource\File;
use Claroline\CoreBundle\Manager\ResourceManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 *  @DI\Service()
 */
class AudioRecorderListener
{
    private $container;
    private $tokenStorage;
    private $fileDir;
    private $rm;

    /**
     * @DI\InjectParams({
     *      "container" = @DI\Inject("service_container"),
     *      "fileDir"    = @DI\Inject("%claroline.param.files_directory%"),
     *      "rm"         = @DI\Inject("claroline.manager.resource_manager")
     * })
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->tokenStorage = $container->get('security.token_storage');
        $this->fileDir = $fileDir;
        $this->rm = $rm;
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
            'parentId' => $resource->getResourceNode()->getId(),
            'resourceType' => 'file',
                )
        );
        $event->setResponse(new RedirectResponse($route));
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("create_api_webrtc_audio")
     *
     * @param CreateResourceEvent $event
     */
    public function onApiCreate(CreateResourceEvent $event)
    {
        $request = $this->container->get('request');
        $formData = $request->request->all();
        $blob = $request->files->get('file');
        $size = $blob->getSize();

        $isFirefox = $formData['nav'] === 'firefox';
        $uid = md5(uniqid());
        $ext = $isFirefox ? 'ogg' : 'wav';

        //print_r($options);

        $fileName = $uid.'.'.$ext;
        $encodedName = $uid.'.mp3';



        $user = $this->tokenStorage->getToken()->getUser();
        $workspace = $user->getPersonalWorkspace();


        // upload file in temp dir
        $tempUploadDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
        $blob->move($tempUploadDir, $fileName);

        // encode file
        $cmd = 'avconv -i '. $tempUploadDir . DIRECTORY_SEPARATOR . $fileName .' -acodec libmp3lame -ab 128k ' . $tempUploadDir . DIRECTORY_SEPARATOR . $encodedName;

        // remove original file
        // @unlink($filename);
        $output;
        $returnVar;
        exec($cmd, $output, $returnVar);

        // error
        if ($returnVar !== 0) {
          return new JsonResponse('encoding error ' . $cmd, 400);
        }

        $fs = new Filesystem();
        $uploadDir = $this->fileDir . DIRECTORY_SEPARATOR .  'WORKSPACE_' . $workspace->getId();
        if(!$fs->exists($uploadDir)){
          die(1);
        }

        $hashName = 'WORKSPACE_' . $workspace->getId() .
                DIRECTORY_SEPARATOR .
                $this->container->get('claroline.utilities.misc')->generateGuid() .
                "." .
                $ext;

        $fs->copy($tempUploadDir . DIRECTORY_SEPARATOR . $uid . '.mp3', $uploadDir . DIRECTORY_SEPARATOR . $uid . '.mp3');

        $rt = $this->rm->getResourceTypeByName('file');
        if(!$rt){
            $rt = new ResourceType();
            $rt->setName('file');
        }

        $file = new File();
        $file->setSize($size);
        $file->setName($encodedName);
        $file->setHashName($hashName);
        $file->setMimeType('audio/mp3');
        $parent = $this->rm->getWorkspaceRoot($workspace);

        //$resource = $this->rm->create($file, $rt, $user, $workspace, $parent);

        @unlink($tempUploadDir . DIRECTORY_SEPARATOR . $fileName);
        @unlink($tempUploadDir . DIRECTORY_SEPARATOR . $encodedName);

        $event->setResources([$file]);
        $event->stopPropagation();

        return new JsonResponse(null, 200);

        // get personnal directory
        //$parent = $this->rm->getWorkspaceRoot($workspace);
        // the name of the resource is blob... thats a pbm...
        //$resource = $this->rm->create($file, $rt, $user, $workspace, $parent);


        /*


        try {
            $fs->mkdir('/tmp/random/dir/'.mt_rand());

            // this directory exists, return true
            $fs->exists('/tmp/photos');
            $fs->copy('image-ICC.jpg', 'image.jpg');


            $fs->remove(array('symlink', '/path/to/directory', 'activity.log'));


        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while creating your directory at '.$e->getPath();
        }
*/
        echo 'mymymymymymy '.$fileName;
        die;

        return false;



        // move encoded file


        // create resource....

        /*$workspace = $user->getPersonalWorkspace();
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
        $file->setMimeType('audio/' .$ext);*/

        // get personnal directory
        //$parent = $this->rm->getWorkspaceRoot($workspace);
        // the name of the resource is blob... thats a pbm...
        //$resource = $this->rm->create($file, $rt, $user, $workspace, $parent);
        //$this->fileManager->uploadContent($resource, $uploaded);


        /*$form->submit($request);

        if ($form->isValid()) {
            $this->handleFileCreation($form, $event);
        }*/



        /*
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

        */
    }

    private function handleFileCreation($form, CreateResourceEvent $event)
    {
        $workspace = $event->getParent()->getWorkspace();
        $workspaceDir = $this->workspaceManager->getStorageDirectory($workspace);
        $isStorageLeft = $this->resourceManager->checkEnoughStorageSpaceLeft(
            $workspace,
            $form->get('file')->getData()
        );

        if (!$isStorageLeft) {
            $this->resourceManager->addStorageExceededFormError(
                $form, filesize($form->get('file')->getData()), $workspace
            );
        } else {
            //check if there is enough space liedt
            //$file is the entity
            //$tmpFile is the other file
            $file = $form->getData();
            $tmpFile = $form->get('file')->getData();

            //the tmpFile may require some encoding.
            if ($encoding = $event->getEncoding() !== 'none') {
                $tmpFile = $this->encodeFile($tmpFile, $event->getEncoding());
            }

            $published = $form->get('published')->getData();
            $event->setPublished($published);
            $fileName = $tmpFile->getClientOriginalName();
            $ext = strtolower($tmpFile->getClientOriginalExtension());
            $mimeType = $this->container->get('claroline.utilities.mime_type_guesser')->guess($ext);

            if (!is_dir($workspaceDir)) {
                mkdir($workspaceDir);
            }

            if (pathinfo($fileName, PATHINFO_EXTENSION) === 'zip' && $form->get('uncompress')->getData()) {
                $roots = $this->unzip($tmpFile, $event->getParent(), $published);
                $event->setResources($roots);

                //do not process the resources afterwards because nodes have been created with the unzip function.
                $event->setProcess(false);
                $event->stopPropagation();
            } else {
                $file = $this->createFile(
                    $file,
                    $tmpFile,
                    $fileName,
                    $mimeType,
                    $workspace
                );
                $event->setResources(array($file));
                $event->stopPropagation();
            }
        }
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
          'InnovaAudioRecorderBundle:AudioRecorder:form.html.twig'
        );
        $event->setResponseContent($content);
        $event->stopPropagation();
    }
}

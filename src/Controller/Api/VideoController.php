<?php

namespace App\Controller\Api;

use App\Entity\Video;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Services\JwtAuth;
use App\Services\Utils\CheckRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

/**
 * @Route("/video")
 */
class VideoController extends AbstractController
{

    /**
     * Serialize response
     *
     * @param $data
     * @return Response
     */
    private function resJson($data): Response
    {
        // Serializar datos con servicio serializer
        $json = $this->get('serializer')->serialize($data, 'json');

        // Response con http-foundation
        $response = new Response();

        // Asignar contenido a la respuesta
        $response->setContent($json);

        // Indicar formato de respuesta
        $response->headers->set('Content-Type', 'application/json');

        // Devolver la respuesta
        return $response;
    }

    //TODO: añadir paginacion
    /**
     * Lists all videos.
     *
     * @Route("/", methods={"GET"}, name="app_video_show")
     *
     * @param Request $request
     * @param CheckRequest $checkRequest
     * @param JwtAuth $jwtAuth
     * @param VideoRepository $videoRepository
     * @return response
     */
    public function index(
        Request $request,
        CheckRequest $checkRequest,
        JwtAuth $jwtAuth,
        VideoRepository $videoRepository
    ): Response {
        // check request with checkRequest service
        $validateRequest = $checkRequest->validateRequest($request);
        if (!$validateRequest) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "API cant received request parameters";

            return $this->resJson($data);
        }

        // Default response
        $data = [];

        // Check data from request
        // Get auth headers(token)
        $authToken = $request->headers->get('Authorization');
        if (!isset($authToken) || empty($authToken)) {
            $data['status']  = 'error';
            $data['code']    = 400;
            $data['message'] = "Forbidden access. API cannot received authorization token";

            return $this->resJson($data);
        }

        // Make service checkAuthToken
        $checkAuthToken = $jwtAuth->checkAuthToken($authToken, $identity = true);
        // return obj($identity = true) | array,

        if (!is_object($checkAuthToken) && $checkAuthToken['status'] === 'error') {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in user update. Try again.";
            $data['error']   = $checkAuthToken['message'];

            return $this->resJson($data);
        }

        $videos = $videoRepository->findAll();
        if (count($videos) <= 0) {
            return $this->resJson([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Ops nothing to see. Create a new video',
            ]);
        }

        return $this->resJson([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Videos list',
            'videos'  => $videos,
        ]);
    }

    /**
     * Create new video
     *
     * @Route("/create", methods={"POST"}, name="app_video_create")
     * @param Request $request
     * @param VideoRepository $videoRepository
     * @param UserRepository $userRepository
     * @param JwtAuth $jwtAuth
     * @param CheckRequest $checkRequest
     * @return Response
     */
    public function create(
        Request $request,
        VideoRepository $videoRepository,
        UserRepository $userRepository,
        JwtAuth $jwtAuth,
        CheckRequest $checkRequest
    ): Response {

        // check request with checkRequest service
        $validateRequest = $checkRequest->validateRequest($request);
        if (!$validateRequest) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "API cant received request parameters";

            return $this->resJson($data);
        }

        // Default response
        $data = [];

        // Check data from request
        // Get auth headers(token)
        $authToken = $request->headers->get('Authorization');
        if (!isset($authToken) || empty($authToken)) {
            $data['status']  = 'error';
            $data['code']    = 400;
            $data['message'] = "Forbidden access. API cannot received authorization token";

            return $this->resJson($data);
        }

        // Make service checkAuthToken
        $checkAuthToken = $jwtAuth->checkAuthToken($authToken, $identity = true);
        // return obj($identity = true) | array,

        if (!is_object($checkAuthToken) && $checkAuthToken['status'] === 'error') {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in video create. Try again.";
            $data['error']   = $checkAuthToken['message'];

            return $this->resJson($data);
        }


        try {
            // Get data from request
            $params = json_decode($request->getContent());

        } catch (Exception $error) {
            $data['status']  = "error";
            $data['code']    = 400;
            $data['message'] = "Error. Something wrong in video create. Try again.";
            $data['error']   = $error;

            return $this->resJson($data);
        }

        //
        $title       = (!empty($params->title)) ? trim($params->title) : null;
        $description = (!empty($params->description)) ? trim($params->description) : null;
        $url         = (!empty($params->url)) ? trim($params->url) : null;

        if (is_null($title) || is_null($description) || is_null($url)) {
            return $this->resJson([
                'status'  => "error",
                'code'    => 400,
                'message' => "Any field from create video form is empty.",
            ]);
        }

        // validate data
        //Validate form fields
        $validator = Validation::createValidator();

        // title
        $validateTitle = $validator->validate($title, [
            new NotBlank(),
            new Length([
                'min' => 2,
                'max' => 100,
            ]),
        ]);
        if (count($validateTitle) == !0) {
            $data['code']  = 400;
            $data['error'] = 'title field is not valid';

            return $this->resJson($data);
        }

        // description
        $validateDescription = $validator->validate($description, [
            new NotBlank(),

        ]);
        if (count($validateDescription) == !0) {
            $data['code']  = 400;
            $data['error'] = 'description field is not valid';

            return $this->resJson($data);
        }

        // description
        $validateUrl = $validator->validate($url, [
            new Url(),
        ]);
        if (count($validateUrl) == !0) {
            $data['code']  = 400;
            $data['error'] = 'url field is not valid';

            return $this->resJson($data);
        }
        // get userId from logged user
        $userId = $checkAuthToken->getId();

        // create new obj video
        $user = $userRepository->findOneBy([
            'id' =>  $userId,
        ]);

        $video  = new Video($title, $description, $url, $user);
        $video->setStatus('normal');

        // save in db
        $doctrine = $this->getDoctrine();
        $em       = $doctrine->getManager();
        $em->persist($video);
        $em->flush();


        // return response
        return $this->resJson([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'The video was recorded successfully!!',
            'video'   => $video,
        ]);


    }
}

<?php
/**
 * @file
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Rest\RestContent;

final class RestController extends Controller
{
    private $lastMethod;
    private $rawContent;

    /**
     * @Route("/content")
     */
    public function contentAction(Request $request) {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $restContent = new RestContent($this->rawContent);
        $isValid = $restContent->validateRequest();

        $response = $this->setResponse($isValid, $restContent->getLastMessage(), $this->lastMethod, $restContent->getParsedBody());
        return $response;
    }

    public function setResponse($status = TRUE, $message = '', $method = 'GET', $content = '')
    {
        $responseContent = array(
            'status' => $status,
            'message' => $message,
            'method' => $method,
            'content' => $content,
        );

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}

<?php
/**
 * @file
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Exception\RestException;
use AppBundle\Rest\RestContent;

final class RestController extends Controller
{
    private $lastStatus;
    private $lastMessage;
    private $lastMethod;
    private $rawContent;

    public function __construct()
    {
        $this->lastMessage = '';
        $this->lastStatus = FALSE;
    }

    /**
     * @Route("/content")
     */
    public function contentAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $em = $this->get('doctrine_mongodb');

        $restContent = new RestContent($em);

        try
        {
            $restContent->setRequestBody($this->rawContent);
            $result = $restContent->handleRequest($this->lastMethod);
            $this->lastMessage = $result;
            $this->lastStatus = TRUE;
        }
        catch (RestException $exc)
        {
            $this->lastMessage = 'Request fault: ' . $exc->getMessage();
        }
        catch (\Exception $exc)
        {
            $this->lastMessage = 'Generic fault: ' . $exc->getMessage();
        }

        $response = $this->setResponse($this->lastStatus, $this->lastMessage, $this->lastMethod, $restContent->getParsedBody());

        return $response;
    }

    public function setResponse($status = TRUE, $message = '')
    {
        $responseContent = array(
            'status' => $status,
            'message' => $message,
        );

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}

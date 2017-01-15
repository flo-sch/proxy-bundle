<?php

namespace Flosch\Bundle\ProxyBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use GuzzleHttp\Client as HttpClient;

class ProxyController extends Controller
{
    public function requestAction($uri, Request $request)
    {
        $response = new Response();

        $client = new HttpClient([
            'base_uri' => $this->getParameter('proxy_base_url')
        ]);

        $proxyResponse = $client->request(
            $request->getMethod(),
            $uri,
            [
                'allow_redirects' => false,
                'http_errors' => false,
                'query' => $request->query->all(),
                'body' => $request->getContent()
            ]
        );

        if ($proxyResponse->getStatusCode() >= 300) {
            if ($proxyResponse->getStatusCode() < 400) {
                // If the Response is an HTTP redirection, physically redirect to the target
                $location = $proxyResponse->getHeader('location')[0];

                // Strip any potential first "/" as the internal flosch_proxy_app_proxy_page already includes one
                if (substr($location, 0, 1) === '/') {
                    $location = substr($location, 1);
                }

                return $this->redirectToRoute('flosch_proxy_request_page', [
                    'uri' => $location
                ]);
            }
        }

        $response->setStatusCode($proxyResponse->getStatusCode());
        $response->headers->replace($proxyResponse->getHeaders());

        $response->setContent($proxyResponse->getBody());

        return $response;
    }
}

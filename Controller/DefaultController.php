<?php

namespace Mmd\Bundle\GoogleWebfontsCacheBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Predis\Client as PredisClient;

class DefaultController extends Controller
{
    private $keyRegex = '/^[0-9a-z\-\_]+$/i';

    private $pathRegex = '/^[0-9a-zA-Z\-\_]+(\/[0-9a-zA-Z\-\_]+)*$/';

    private $predisConnection;

    private $cacheExpireSeconds = 3600;

    /**
     * @return PredisClient
     */
    private function getPredis()
    {
        if (!$this->predisConnection) {
            $this->predisConnection = new PredisClient(
                array(
                    'scheme' => $this->container->getParameter('mmd_google_webfonts_cache.redis.scheme'),
                    'host'   => $this->container->getParameter('mmd_google_webfonts_cache.redis.host'),
                    'port'   => $this->container->getParameter('mmd_google_webfonts_cache.redis.port'),
                ),
                $this->container->getParameter('mmd_google_webfonts_cache.redis.options')
            );
        }

        return $this->predisConnection;
    }

    /**
     * @param string $path
     * @return array|null
     */
    private function getCache($path)
    {
        $key = md5($path);

        return json_decode(
            $this->getPredis()->get($key),
            true
        );
    }

    /**
     * @param string $path
     * @param array $response
     */
    private function setCache($path, $response)
    {
        $key = md5($path);

        $this->getPredis()->set(
            $key,
            json_encode($response)
        );

        $this->getPredis()->expire(
            $key,
            $this->cacheExpireSeconds
        );
    }

    public function apiAction(Request $request, $path)
    {
        $response = array(
            'code' => 500,
            'content' => array(
                'message' => 'Unknown'
            ),
        );

        do {
            if (!preg_match($this->pathRegex, $path)) {
                $response['code'] = 400;
                $response['content']['message'] = 'Path not allowed';
                break;
            }

            if ($cachedResponse = $this->getCache($path)) {
                $response = $cachedResponse;
                unset($cachedResponse);
                break;
            }

            {
                $key = $this->container->getParameter('mmd_google_webfonts_cache.key');

                if (!preg_match($this->keyRegex, $key)) {
                    $response['content']['message'] = 'Invalid key parameter';
                    break;
                }
            }

            /**
             * @var \Buzz\Browser $buzz
             */
            $buzz = $this->get('buzz');

            /**
             * @var \Buzz\Message\Response $apiResponse
             */
            $apiResponse = $buzz->get(
                'https://www.googleapis.com/webfonts/' . $path
                . '?key='. $key
            );

            $response['code'] = $apiResponse->getStatusCode();
            $response['content'] = $apiResponse->getContent();

            unset($apiResponse);

            if ($response['code'] === 200) {
                $this->setCache($path, $response);
            }
        } while(false);

        return new Response(
            is_array($response['content'])
                ? json_encode($response['content'], defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0)
                : $response['content'],
            $response['code']
        );
    }
    
    public function indexAction()
    {
        return $this->render('MmdGoogleWebfontsCacheBundle:Default:index.html.twig', array());
    }
}

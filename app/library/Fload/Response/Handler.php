<?php
/**
 * @author Stephen "TheCodeAssassin" Hoogendijk <admin@tca0.nl>
 */

namespace Fload\Response;


use Fload\DataProvider\Exception as DataProviderError;
use Fload\Exception as FloadException;
use Slim\Slim;

/**
 * Class Handler
 * @package Fload\Response
 */
class Handler
{
    /**
     * @var Slim
     */
    protected $app;

    /**
     * @param Slim $app
     */
    public function __construct(Slim $app)
    {
        $app->response->headers->set('Content-Type', 'application/json');

        $app->notFound(
            function () use ($app) {
                echo JsonResponse::response(JsonResponse::PAGE_NOT_FOUND, 'Page not found');
            }
        );

        $this->app = $app;
    }

    /**
     * @param \Exception $exception
     */
    public function handleException(\Exception $exception)
    {
        if ($exception instanceof FloadException) {
            echo JsonResponse::response(JsonResponse::CODE_FLOAD_EXCEPTION, $exception->getMessage());
        } elseif ($exception instanceof DataProviderError) {
            echo JsonResponse::response(JsonResponse::CODE_DATAPROVIDER_ERROR, $exception->getMessage());
        } else {
            echo JsonResponse::response(JsonResponse::CODE_UNKNOWN_ERROR, $exception->getMessage());
        }

        $this->app->getLog()->error($exception);
    }
}

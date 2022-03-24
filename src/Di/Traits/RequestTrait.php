<?php

namespace Jaxon\Di\Traits;

use Jaxon\Config\ConfigManager;
use Jaxon\Di\Container;
use Jaxon\Plugin\Manager\PluginManager;
use Jaxon\Request\Factory\Factory;
use Jaxon\Request\Factory\ParameterFactory;
use Jaxon\Request\Factory\RequestFactory;
use Jaxon\Request\Handler\ArgumentManager;
use Jaxon\Request\Handler\CallbackManager;
use Jaxon\Request\Handler\RequestHandler;
use Jaxon\Request\Handler\UploadHandler;
use Jaxon\Request\Plugin\CallableClass\CallableRegistry;
use Jaxon\Response\Plugin\DataBag\DataBagPlugin;
use Jaxon\Response\ResponseManager;
use Jaxon\Ui\Dialogs\DialogFacade;
use Jaxon\Ui\Pagination\Paginator;
use Jaxon\Utils\Translation\Translator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

trait RequestTrait
{
    /**
     * Register the values into the container
     *
     * @return void
     */
    private function registerRequests()
    {
        // The server request
        $this->set(ServerRequestInterface::class, function() {
            $xRequestFactory = new Psr17Factory();
            $xRequestCreator = new ServerRequestCreator(
                $xRequestFactory, // ServerRequestFactory
                $xRequestFactory, // UriFactory
                $xRequestFactory, // UploadedFileFactory
                $xRequestFactory  // StreamFactory
            );
            return $xRequestCreator->fromGlobals();
        });
        // Argument Manager
        $this->set(ArgumentManager::class, function($c) {
            return new ArgumentManager($c->g(ServerRequestInterface::class), $c->g(ConfigManager::class), $c->g(Translator::class));
        });
        // Callback Manager
        $this->set(CallbackManager::class, function() {
            return new CallbackManager();
        });
        // Request Handler
        $this->set(RequestHandler::class, function($c) {
            return new RequestHandler($c->g(Container::class), $c->g(ConfigManager::class),
                $c->g(ArgumentManager::class), $c->g(PluginManager::class), $c->g(ResponseManager::class),
                $c->g(CallbackManager::class), $c->g(ServerRequestInterface::class), $c->g(UploadHandler::class),
                $c->g(DataBagPlugin::class), $c->g(Translator::class));
        });
        // Request Factory
        $this->set(Factory::class, function($c) {
            return new Factory($c->g(CallableRegistry::class), $c->g(RequestFactory::class),
                $c->g(ParameterFactory::class));
        });
        // Factory for requests to functions
        $this->set(RequestFactory::class, function($c) {
            $sPrefix = $c->g(ConfigManager::class)->getOption('core.prefix.function');
            return new RequestFactory($sPrefix, $c->g(DialogFacade::class), $c->g(Paginator::class));
        });
        // Parameter Factory
        $this->set(ParameterFactory::class, function() {
            return new ParameterFactory();
        });
    }

    /**
     * Get the request handler
     *
     * @return RequestHandler
     */
    public function getRequestHandler(): RequestHandler
    {
        return $this->g(RequestHandler::class);
    }
}

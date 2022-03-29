<?php

use Jaxon\CallableClass;
use Jaxon\Response\Response;
use Jaxon\Response\UploadResponse;

class Misc extends CallableClass
{
    public function simple(): Response
    {
        $this->response->alert('This is the global response!');
        return $this->response;
    }

    public function merge(): Response
    {
        $this->response->alert('This is the global response!');

        $xResponse = jaxon()->newResponse();
        $xResponse->debug('This is a different response!');
        return $xResponse;
    }

    public function appendbefore(): Response
    {
        $this->response->alert('This is the global response!');
        $xResponse = jaxon()->newResponse();
        $xResponse->debug('This is a different response!');
        // Merge responses
        $this->response->appendResponse($xResponse, true);
        return $this->response;
    }

    public function mergeWithUpload(): UploadResponse
    {
        $this->response->alert('This is the global response!');

        $xResponse = new UploadResponse();
        $xResponse->debug('This is a different response!');
        return $xResponse;
    }
}
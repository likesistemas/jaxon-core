<?php

use Jaxon\CallableClass;
use Jaxon\Response\Response;
use Jaxon\Response\UploadResponse;

class TestHk extends CallableClass
{
    protected function before()
    {
        $this->response->alert('This is the before hook!');
    }

    protected function before2()
    {
        $this->response->alert('This is the before2 hook!');
        $this->response->html('menu-id', 'This is the menu content!');
    }

    protected function after()
    {
        $this->response->alert('This is the after hook!');
    }

    protected function after1($param)
    {
        $this->response->alert("This is the after1 hook, with param $param!");
    }

    protected function after2($param1, $param2)
    {
        $this->response->alert("This is the after1 hook, with params $param1 and $param2!");
    }

    public function all(): Response
    {
        $this->response->alert('This is the all method!');
        return $this->response;
    }

    public function one(): Response
    {
        $this->response->alert('This is the one method!');
        return $this->response;
    }

    public function two(): Response
    {
        $this->response->html('div-id', 'This is the two method!');
        $this->response->alert('This is the two method!');
        return $this->response;
    }

    public function three(): Response
    {
        $this->response->html('div-id', 'This is the three method!');
        return $this->response;
    }

    public function four(): Response
    {
        $this->response->html('div-id', 'This is the four method!');
        return $this->response;
    }
}
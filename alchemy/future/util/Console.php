<?php
/**
 * Copyright (C) 2012 Dawid Kraczkowski
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR
 * A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace alchemy\future\util;
use alchemy\util\CLI;

class Console extends \alchemy\app\Application
{
    protected function __construct()
    {
        \alchemy\http\router\Route::setSeparator(':');
        parent::__construct();
        $this->router->setForceMode();
    }

    public function run($mode = null)
    {
        $cli = fopen('php://stdin' , 'r');
        if ($this->onStartupHandler && $this->onStartupHandler->isCallable()) {
            $this->onStartupHandler->call();
        }
        while (true) {
            $this->input = $input = trim(fgets($cli, 1024));
            try {
                if (!$this->context) {
                    $this->router->setURI($input);
                    $route = $this->router->getRoute(true);
                    $resource = $this->router->getResource(true);
                    $resource->call($route->getParameters());
                } else {
                    call_user_func($this->context, $input);
                }
            } catch (\Exception $e) {
                if ($this->onErrorHandler && $this->onErrorHandler->isCallable()) { //is app error handler registered
                    $this->onErrorHandler->call($e);
                } else {
                    throw $e;
                }
            }

        }
    }

    public function switchContext($callable)
    {
        $this->context = $callable;
    }

    public function removeContext()
    {
        $this->context = null;
    }

    public function getInput()
    {
        return $this->input;
    }

    protected $input;

    /**
     * Context when cli commands will go
     * @var
     */
    protected $context;

}

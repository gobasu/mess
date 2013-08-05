<?php
namespace mess\view;
use alchemy\template\Mixture;
use alchemy\app\View;

class HomeView extends View
{
    public function render()
    {
        $renderer = new Mixture(dirname(__FILE__) . '/../public/theme/default/');
        return $renderer->render('hello.html', $this->vars);
    }
}
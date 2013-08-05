<?php
namespace mess\controller;
use alchemy\app\Controller;
use mess\view\HomeView;

class HomeController extends Controller
{
    public function indexAction($request)
    {
        $view = new HomeView();
        $view->msg = "Goodbye poor world!";
        echo $view;
    }
}
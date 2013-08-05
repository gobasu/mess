<?php
require_once "../../alchemy/app/Application.php";

$app = \alchemy\app\Application::instance();

$app->setApplicationDir(realpath(dirname(__FILE__) . '/../'));

$app->onURI('*', 'mess\controller\HomeController->indexAction');
$app->run();
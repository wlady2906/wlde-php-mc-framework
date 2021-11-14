<?php

namespace app\core;
use app\core\Router;
use app\models\User;
use app\core\Request;
use app\core\Session;
use app\core\Response;
use app\core\Controller;
use app\core\db\DbModel;
use app\core\db\Database;
use app\helpers\BaseHelper;

class Application
{ 

    //Applications events can be used to register activities from clients
    //We can use them to log the database o services being consumed
    const EVENT_BEFORE_REQUEST = 'beforeRequest';
    const EVENT_AFTER_REQUEST = 'afterRequest';

    protected array $eventListeners = [];

    public static string $ROOT_DIR;
    public string $layout = 'main';
    public string $userClass;
    public Router $router;
    public Request $request;
    public Response $response;
    public Session $session;
    public Database $db;
    //public ?DbModel $user;
    public ?UserModel $user;
    public View $view;


    public static Application $app;
    public ?Controller $controller = null;
    

    public function __construct($rootPath, array $config)
    {
        
        self::$ROOT_DIR = $rootPath;
        self::$app = $this;
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->router = new Router($this->request, $this->response);
        $this->view = new View();

        $this->db = new Database($config['db']);
        $this->userClass = $config['userClass'];
        

        $primaryValue = $this->session->get('user');
        if($primaryValue):
            $primaryKey = $this->userClass::primaryKey();
            $this->user = $this->userClass::findOne([$primaryKey => $primaryValue]);
        else:
            $this->user = null;
        endif;
        
 
    }
    public function run(){
        $this->triggerEvent(self::EVENT_BEFORE_REQUEST);
        try {
            echo $this->router->resolve();
        } catch (\Throwable $th) {
            $this->response->setStatusCode($th->getCode());
            echo $this->view->renderView('error', [
                'exception' => $th
            ]);
        }
        
    }   

    public function getController(){
        return $this->controller;
    }

    public function setController(Controller $controller){
         $this->controller = $controller;
    }

    public function login(UserModel $user)
    {
        $this->user = $user;
        $primaryKey = $user->primaryKey();
        $primaryValue = $user->{$primaryKey};
        $this->session->set('user', $primaryValue);
        return true;
    }

    public function logout(){
        $this->user = null;
        $this->session->remove('user');
    }

    public static function isGuest(){
        return !self::$app->user;
    }

    public function on($eventName, $callback)
    {
        $this->eventListeners[$eventName][] = $callback;
    }

    public function triggerEvent($eventName){
        $callbacks = $this->eventListeners[$eventName] ?? [];
        foreach($callbacks as $callback):
            call_user_func($callback);
        endforeach;
    }
}

?>
<?php

class OverseasTraining extends Controller{
    public function __construct($controller , $action){
      parent::__construct($controller,$action);
    }
    public function indexAction(){
      $this->view->render('overseasTraining/index');
    }
}
<?php

class Router{

    public static function route($url){

      //controller
      //ucwords($name) is used to convert the first character into  upper case
      $controller =  (isset($url[0])  && $url[0] != "" ) ?  ucwords($url[0]): DEFAULT_CONTROLLER ;
      $controller_name=$controller;
      array_shift($url); //remove the first element in the array


      //action
      $action =  (isset($url[0])  && $url[0] != "" ) ?  $url[0]. "Action": "indexAction" ;
      $action_name= (isset($url[0]) && $url[0] !='')? $url[0] : 'index';
      array_shift($url); //remove the first element in the array

      //check whether the user has access or not (acl check)
       $grantAccess = self::hasAccess($controller_name,$action_name);
       if(!$grantAccess){
          $controller_name = $controller = ACCESS_RESTRICTED;
          $action ='indexAction';
       }


      //params
      $queryparams=$url;

      $dispatch = new $controller($controller_name,$action);

      //method_exists(class_name,method_name)
      //call_user_fun_array([object,method],parameters)
      if (method_exists($controller,$action)){
        call_user_func_array([$dispatch , $action],$queryparams);
		//similar to dispatch -> registerindex($queryparam);
      }else{
        die ('The method does not exist in the controller \"'. $controller_name . '\"');
      }

    }

    public static function redirect($location){
      if(!headers_sent()){
        header('Location: '.PROOT.$location);
      }else{
        echo '<script type="text/javascript">';
        echo 'window.location.href="'.PROOT.$location.'";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url='.$location.'"/>';
        echo '</noscript>';exit;
      }
    }

    public static function hasAccess($controller_name,$action_name='index'){
      $acl_file = file_get_contents(ROOT.DS.'app'.DS.'acl.json');
      $acl = json_decode($acl_file,true); //if true ,gives an array instead of object
      $current_user_acls = ["Guest"];
      $grantAccess= false;
      
      if(Session::exists(CURRENT_USER_SESSION_NAME)){
        $current_user_acls[] = "LoggedIn";
        // foreach(currentUser()->acls() as $a){
        //   $current_user_acls[] = $a;        } // adding the acls included in the database 
        if (currentUser()->acls()){
          $current_user_acls=currentUser()->acls();
        }
      }

      foreach($current_user_acls as $level){
        if(array_key_exists($level,$acl) && array_key_exists($controller_name,$acl[$level])){
          if (in_array($action_name , $acl[$level][$controller_name]) || in_array('*',$acl[$level][$controller_name])){
            $grantAccess = true;
            break;
          }
        }
      }

      //Check for denied
      foreach($current_user_acls as $level){
        $denied = $acl[$level]['denied'];
        if(!empty($denied) && array_key_exists($controller_name,$denied) && in_array($action_name,$denied[$controller_name])){
          $grantAccess=false;
          break;
        }
      }

      return $grantAccess;
    }

    public static function getMenu($menu){
      $menuAry=[];
      $menuFile = file_get_contents(ROOT .DS.'app'.DS.$menu.'.json');
      $acl = json_decode($menuFile,true);
      
      foreach($acl as $key => $val){
        if(is_array($val)){
          $sub=[];
          foreach($val as $k => $v){
            if ($k == 'separator' && !empty($sub)){
              $sub[$k] = '';
              continue;
            }else if ($finalVal = self::get_link($v)){
              $sub[$k] = $finalVal;
            }
          }
          if(!empty($sub)){
            $menuAry[$key] = $sub;
          }
        }else {
          if($finalVal = self::get_link($val)){
            $menuAry[$key] = $finalVal;
          }
        }
      }
      return $menuAry;
    }

    private static function get_link($val){
      //check if external link 
      if(preg_match('/https?:\/\//' , $val )==1){
        return $val;
      }else{
        $uAry = explode('/',$val);
        $controller_name = ucwords($uAry[0]);
        $action_name = (isset($uAry[1]))?$uAry[1]:'';
        if(self::hasAccess($controller_name,$action_name)){
          return PROOT.$val; //if there is access
        }
        return false; // if there is no access
      }
    }

    
}

//json-decode(filename,true) ..........creates an array from the datas included in the relevant file 
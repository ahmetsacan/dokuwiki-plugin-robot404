<?php
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_robot404 extends DokuWiki_Action_Plugin {
  function getInfo(){ return conf_loadfile(dirname(__FILE__).'/info.txt'); }
	  function register($contr){
    if(static::client_isrobot()){
      $contr->register_hook('ACTION_ACT_PREPROCESS','BEFORE',$this,'handle_action');
    }
    else{
      #In case our robot detection does not capture a robot, also hook to headers so we can add noindex,nofollow to these pages.
      $contr->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE',  $this, 'handle_header');
      $contr->register_hook('ACTION_HEADERS_SEND', 'BEFORE',  $this, 'handle_header');
    }
   }
   function handle_header(&$e){
    global $ACT;
    #looking at $ACT is not enough (e.g, if 'register' action is disabled, ACT becomes 'show'. We also check the original 'do' parameter.)
    if(!$this->ishiddenpage()&&!$this->isdisabledaction($ACT)&&!$this->isdisabledaction($_REQUEST['do'])) return;
    if ($e->name == 'TPL_METAHEADER_OUTPUT'){
    $found=false;
    foreach($e->data['meta']?:[] as $key=>$entry){
      if($entry['name']=='robots'){
        $content=explode(',',$entry['content']); #e.g., convert 'index,follow' to an array.
        $content=array_diff($content,['index','follow']); #remove 'index' and 'follow' from the array.
        $content[]='noindex'; #add noindex and nofollow
        $content[]='nofollow';
        #$content[]='addedbyrobot404'; #used for debugging.
        $entry['content']=implode(',',$content);
        $e->data['meta'][$key]=$entry;
        $found=true;
      }
    }
    if(!$found) $e->data['meta'][]=['name'=>'robots','content'=>'noindex,nofollow'];
    }
    elseif ($e->name == 'ACTION_HEADERS_SEND')
        $e->data[] = 'X-Robots-Tag: noindex,nofollow';
   }

  static function client_isrobot(){
    if(isset($_REQUEST['isrobot404'])&&$_REQUEST['isrobot404']) return true;
    if(!isset($_SERVER['HTTP_USER_AGENT'])||!$_SERVER['HTTP_USER_AGENT']) return false;
    if(preg_match('#(Google|msnbot|Yahoo|Rambler|AbachoBOT|accoona|AcioRobot|ASPSeek|CocoCrawler|Dumbot|FAST-WebCrawler|GeonaBot|Gigabot|Lycos|MSRBOT|Scooter|AltaVista|IDBot|eStyle|Scrubby|BaiDuSpider|Baiduspider)#i',$_SERVER['HTTP_USER_AGENT'],$m)) return strtolower($m[1]);
          #also use the list in: https://stackoverflow.com/questions/677419/how-to-detect-search-engine-bots-with-php
          if(preg_match('#(bot|crawl|slurp|spider|mediapartners)#i',$_SERVER['HTTP_USER_AGENT'],$m)) return strtolower($m[1]);
    return false;
  }

   function ishiddenpage(){
    global $ID;
    return $this->getConf('hiddenpages') && isHiddenPage($ID);
   }
   function isdisabledaction($action){
    if(is_array($action)){
      foreach($action as $act){
        if($this->isdisabledaction($act)) return true;
      }
      return false;
    }
    global $conf;
    $actions=$conf['disableactions'];
    if(is_string($actions)) $actions=explode(',',$actions);
    if(in_array($action,$actions)) return true;

    $actions=$this->getConf('disableactions');
    if(is_string($actions)) $actions=explode(',',$actions);
    if(in_array($action,$actions)) return true;

    return false;
   }
   
  function handle_action(&$e){
    if(!$this->ishiddenpage() && !$this->isdisabledaction($e->data)) return; #if !hidden and !disabledaction, nothing to do. return.
    header('HTTP/1.0 404 Not Found');
    echo $this->ishiddenpage()?"Hidden page for robots.":"Disallowed action for robots: ".esc($e->data);
    die();
  }
}

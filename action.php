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
       if ($this->ishidden() === false) return;
       if ($e->name == 'TPL_METAHEADER_OUTPUT'){
        $found=false;
        foreach($e->data['meta']?:[] as &$entry){
          if($entry['name']=='robots'){
            $content=explode(',',$entry['content']); #e.g., convert 'index,follow' to an array.
            $content=array_diff($content,['index','follow']); #remove 'index' and 'follow' from the array.
            $content[]='noindex'; #add noindex and nofollow
            $content[]='nofollow';
            $content[]='addedbyrobot404'; #used for debugging.
            $entry['content']=implode(',',$content);
            $found=true;
          }
        }unset($entry);
        if(!$found) $e->data['meta']=['name'=>'robots','content'=>'noindex,follow'];
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

   function ishidden(){
    global $ID;
    return $this->getConf('hiddenpages') && isHiddenPage($ID);
   }
   
  function handle_action(&$e){
    global $conf;
    $ishidden=$this->ishidden();
    if(!$ishidden){
      $actions=$conf['disableactions'];
      $actions2=$this->getConf('disableactions');
      #ve([$actions,$actions2]);
      if(is_string($actions)) $actions=explode(',',$actions);
      if(is_string($actions2)) $actions2=explode(',',$actions2);
      $actions=array_merge($actions,$actions2);
      if(!in_array($e->data,$actions)) return; #if !hidden and !disabledaction, nothing to do. return. 
    }
    header('HTTP/1.0 404 Not Found');
    echo $ishidden?"Hidden page for robots.":"Disallowed action for robots: ".esc($e->data);
    die();
  }
}

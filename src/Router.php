<?php
/**
 * 
 * @link http://iziweb.vn
 * @copyright Copyright (c) 2016 iziWeb
 * @email zinzinx8@gmail.com
 *
 */
namespace izi\web;
use Yii;
use yii\db\Query;
class Router extends \yii\db\ActiveRecord
{
	/*
	
	*/
	public $settings = [];
	public $_adminRoute = ['admin','acp','apc','cpanel'], $defaultRoute = 'site';
	private $_router = '';
	protected $request;
	public static function tableName(){
	 	return '{{%slugs}}';
	}
	
	public function __construct(){
		$this->request = Yii::$app->request;
		$this->registerServices();
		//Yii::$app->request->url = '/about'; 
	}
	
	protected function registerServices(){
		
		/**
		 * Phân tích dữ liệu từ server header
		 * 
		 */
		$s = $_SERVER;
		$ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
		$sp = strtolower($s['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $s['SERVER_PORT'];
		$port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
		$host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : $s['SERVER_NAME'];
		$path = ($s['REQUEST_URI'] ? $s['REQUEST_URI'] : $_SERVER['HTTP_X_ORIGINAL_URL']);
		$url = $protocol . '://' . $host . $port . $path;
		$pattern = array('/index\.php\//','/index\.php/');
		
		
		
		$replacement = array('','');
		$url = preg_replace($pattern, $replacement, $url);
		$a = parse_url($url);
		$d = array(
				'FULL_URL'=>$url,
				'URL_NO_PARAM'=> $a['scheme'].'://'.$a['host'].$port.$a['path'],
				'URL_WITH_PATH'=>$a['scheme'].'://'.$a['host'].$port.$a['path'],
				'URL_NOT_SCHEME'=>$a['host'].$port.$a['path'],				
				'ABSOLUTE_DOMAIN'=>$a['scheme'].'://'.$a['host'],
				'SITE_ADDRESS'=>\yii\helpers\Url::to('/'),				
				'SCHEME'=>$a['scheme'],
				'DOMAIN'=>$a['host'],
				"__DOMAIN__"=>$a['host'],
				'DOMAIN_NOT_WWW'=>preg_replace('/www./i','',$a['host'],1),
				'URL_NON_WWW'=>preg_replace('/www./i','',$a['host'],1),
				'URL_PORT'=>$port,
				'URL_PATH'=>$a['path'],
				
		);
		foreach($d as $k=>$v){
			defined($k) or define($k,$v);
		}
		// Lấy thông tin shop từ domain đang chạy 		 
		$r = $this->getShopFromDomain();		 	
		$dma = false;
		if(!empty($r)){
			define ('SHOP_TIME_LEFT',countDownDayExpired($r['to_date']));
			define ('SHOP_TIME_LIFE',($r['to_date']));
			define ('SHOP_STATUS',($r['status']));	
			define ('__SID__',(float)$r['sid']);
			define ('__SITE_NAME__',$r['code']);
			define ('__TEMPLETE_DOMAIN_STATUS__',$r['state']);	
			$defaultModule = $r['module'] != "" ? $r['module'] : $this->defaultRoute;
			 
			/*
			 *
			 * */
	
			$pos = strpos($this->request->url, '/sajax');
			if($pos === false){
				$this->defaultRoute = $defaultModule;
			}
			if($r['is_admin'] == 1){
				defined('__IS_ADMIN__') or define('__IS_ADMIN__',true);
				$dma = true;
			}
			 
		}else{			
			define ('SHOP_STATUS',0);
			define ('__SID__',0);
		}
		// Get site config
		
		$this->settings = $this->getConfigs('SETTINGS',false,__SID__,false);
		view2($this->settings,true);
		if(!isset($this->settings['currency']['default'])){
			Yii::$app->c->setDefaultCurrency(1);
			$this->settings = $this->getConfigs('SETTINGS',false,__SID__,false);
		}
		// Set param
		Yii::$app->params['settings'] = $this->settings;
		//
		$suffix = isset($this->settings['url_manager']['suffix']) ? $this->settings['url_manager']['suffix']: '';
		define('URL_SUFFIX', $suffix);
		if(URL_SUFFIX != ""){
			//
			Yii::$app->set('urlManager',[
					'suffix'=>URL_SUFFIX,
					'class' => 'yii\web\UrlManager',
					'showScriptName' => false,
					'enablePrettyUrl' => true,
					'scriptUrl'=>'/index.php',
					'rules' => [
							''=>'site/index',
							[
								'pattern'=>'/',
								'route'=>'/',
								'suffix'=>null	
							],							 
							[
									'pattern'=>'admin/',
									'route'=>'admin/',
									'suffix'=>''
							],
							 
							[
									'pattern'=>'admin/login',
									'route'=>'admin/login',
									'suffix'=>''
							],
							[
									'pattern'=>'admin/logout',
									'route'=>'admin/logout',
									'suffix'=>''
							],
							[
									'pattern'=>'admin/forgot',
									'route'=>'admin/forgot',
									'suffix'=>''
							],
							'<action:\w+>'=>'site/<action>',
							'<alias:sajax>/<view>'=>'site/<alias>',
							'site/<action>'=>'site/<action>',
							'site/<action>/<view>'=>'site/<action>',
							'site/<action>/<view>/<id:\d+>'=>'site/<action>',
							'site/<action>/<view>/<url:\w+>'=>'site/<action>',
							'site/<action>/<view>/<url:\w+>/<url2:\w+>'=>'site/<action>',
							'site/<action>/<view>/<url>/<url2>/<url3>'=>'site/<action>',
							'site/<action>/<view>/<url>/<url2>/<url3>/<url4>'=>'site/<action>',
							'site/<action>/<view>/<url>/<url2>/<url3>/<url4>/<url5>'=>'site/<action>',
							'site/<action>/<view>/<url>/<url2>/<url3>/<url4>/<url5>/<url6>'=>'site/<action>',
							'site/<action>/<view>/<url>/<url2>/<url3>/<url4>/<url5>/<url6>/<url7>'=>'site/<action>',
							'gii'=>'gii/default/index',
							'gii/<controller>'=>'gii/<controller>',
							'gii/<controller>/<action>'=>'gii/<controller>/<action>',
							'<module:\w+>'=>'<module>/default/index',
							'<module:\w+><alias:index|default>'=>'<module>/default',
							'<module:\w+>/<alias:login|logout|forgot>'=>'<module>/default/<alias>',
							'<module:\w+>/<controller:\w+>'=>'<module>/<controller>',
							'<module:\w+>/<controller:\w+>/<action:\w+>'=>'<module>/<controller>/<action>',
							'<module:\w+>/<controller:\w+>/<action:update|delete>/<id:\d+>' => '<module>/<controller>/<action>',
							
					],
			]);
		}
		
		//$this->getSiteGroup();
		//
		define('__DOMAIN_ADMIN__',$dma);
		define('__IS_SUSPENDED__',false);//\common\models\Suspended::checkSuspended());
		define('ADMIN_ADDRESS',__DOMAIN_ADMIN__ ? \yii\helpers\Url::to('/') : \yii\helpers\Url::to('/') .  $this->_adminRoute[0]);
		define('ABSOLUTE_ADMIN_ADDRESS',__DOMAIN_ADMIN__ ? ABSOLUTE_DOMAIN : ABSOLUTE_DOMAIN . '/' .  $this->_adminRoute[0]);
		/**
		 * Check is admin module
		 */
		// customize
		$pos = strpos(Yii::$app->request->url, '?');
		$this->_router = $pos !== false ? substr(Yii::$app->request->url, 0, $pos) : Yii::$app->request->url;		 		
		while (strlen($this->_router)>0 && $this->_router[0] == '/'){$this->_router = substr($this->_router, 1);}		
		if(in_array($this->_router, ['sitemap.xml','robots.txt'])){
			$this->_router = str_replace(['.txt','.xml'], '', $this->_router);
		}		
		
		if(URL_SUFFIX != ""){
			$this->_router = str_replace(URL_SUFFIX, '', $this->_router);			
		}		
		$this->_router = explode("/",$this->_router);					
		if(in_array($this->_router[0], array_merge($this->_adminRoute,['gii']))){
			defined('__IS_ADMIN__') or define('__IS_ADMIN__',true);
			$this->defaultRoute = $this->_router[0];
			unset($this->_router[0]); $this->_router = array_values($this->_router);
		}else{
			defined('__IS_ADMIN__') or define('__IS_ADMIN__',false);
		}
		//
		$url = '';
		
		//view2($this->_router,true);
	 
		if(__IS_ADMIN__){
			require_once Yii::getAlias('@common') . '/functions/admin_function.php';
		}
		
		defined("CBASE_URL") or define('CBASE_URL', __IS_ADMIN__ ? ADMIN_ADDRESS : SITE_ADDRESS);
		
		if(!empty($this->_router) && !__IS_ADMIN__){
			
			if(!in_array($this->_router[0], ['tag','tags'])){
			foreach ($r = array_reverse($this->_router) as $url){
				$s = $this->findByUrl($url);
				if(!empty($s)){
					$this->slug = $s;
					 
					if(isset($this->slug['checksum']) && $this->slug['checksum'] != "" 
							&& $this->slug['checksum'] != md5(URL_PATH)){
						// báo link sai						
						$url1 = Yii::$app->zii->getUrl($s['url']);
						if(md5($url1) == $s['checksum']){
							$this->getResponse()->redirect($url1,301);
						}else{
							//$url = 'error';
						}
					}
					break;
				}else{

				}
			}			 
			}
			
		}
		 
		$pos = strpos(Yii::$app->request->url, '.');
		//view2($this->request->url,true);
		//view2($pos);
		
		if($pos !== false){
			//view2($url);
			$url = substr($url, 0,$pos);
		}		
		//view2($url);
		
		/**
		 * Lay lang theo url 
		 */					
		
		if(__IS_ADMIN__){
			$this->setDefaultLanguage();
			foreach ($r = $this->_router as $url){ 
				$this->slug = \app\izi\Slug::adminFindByUrl($url);				
				break;				 
			}
			if(!empty($this->slug)){
				$this->slug['hasChild'] = \app\izi\Slug::checkExistedChild($this->slug['id']);	
				if($this->slug['hasChild']){
					$this->slug['route'] = 'default';
				}
			}else { 
				/*
				$this->slug['hasChild'] = false;
				$this->slug['child_code'] = '';
				$this->slug['type'] = 0;
				$this->slug['route'] = 'default';
				*/
			}
			 
		}		  
		
		defined('__DETAIL_URL__') or define ('__DETAIL_URL__',$url);
		
		if(!__IS_ADMIN__){
			if(strlen(__DETAIL_URL__)>0){
				if(!empty($this->slug)){
					defined('__URL_LANG__') or define('__URL_LANG__', $this->slug['lang']);
				}
			}	
			if(!in_array(__DETAIL_URL__, ['ajax','sajax'])){
				$this->redirect301();
			}
			$this->setDefaultLanguage();
		}
		 
	}
	
	public function getShopFromDomain($domain = __DOMAIN__){		
		$key = md5(session_id() . __DOMAIN__);
		$config = Yii::$app->session->get($key);
		
		if(!YII_DEBUG && !empty($config)){
			return $config;
		}else{
			$config = static::find()
			->select(['a.sid','b.status','b.code','a.is_admin','a.module','b.to_date','a.state'])
			->from(['a'=>'{{%domain_pointer}}'])
			->innerJoin(['b'=>'{{%shops}}'],'a.sid=b.id')
			->where(['a.domain'=>__DOMAIN__])->asArray()->one();			
			Yii::$app->session->set($key, $config);
			return $config;
		}
	}

	public static function getTempleteName($cached =  true){
		defined('__TEMPLETE_DOMAIN_STATUS__') or define('__TEMPLETE_DOMAIN_STATUS__', 1);
		$config = Yii::$app->session->get('config');	
		$c = __SID__ .'_'. PRIVATE_TEMPLETE;
		//view2($c,true); 
		if(!YII_DEBUG && isset($config['templete'][$c][__LANG__]['name']) && $config['templete'][$c][__LANG__]['name'] != ""){	
			return $config['templete'][$c][__LANG__];
		}else{		
			$r = [];
			if(PRIVATE_TEMPLETE>0){
				$r = static::find()
				->select(['a.*'])
				->from(['a'=>'{{%templetes}}'])				 
				->where(['a.id'=>PRIVATE_TEMPLETE])->asArray()->one();
				
			}
			if(empty($r)){
				//
				$r = static::find()
				->select(['a.*'])
				->from(['a'=>'{{%templetes}}'])
				->innerJoin(['b'=>'{{%temp_to_shop}}'],'a.id=b.temp_id')
				->where(['b.state'=>__TEMPLETE_DOMAIN_STATUS__,'b.sid'=>__SID__,'b.lang'=>__LANG__])->asArray()->one();						 
				if(empty($r)){
					$r = static::find()
					->select(['a.*'])
					->from(['a'=>'{{%templetes}}'])
					->innerJoin(['b'=>'{{%temp_to_shop}}'],'a.id=b.temp_id')
					->where(['b.state'=>__TEMPLETE_DOMAIN_STATUS__,'b.sid'=>__SID__])->asArray()->one();
				}
				//
				if(empty($r) && __TEMPLETE_DOMAIN_STATUS__ > 1){
					$r = static::find()
					->select(['a.*'])
					->from(['a'=>'{{%templetes}}'])
					->innerJoin(['b'=>'{{%temp_to_shop}}'],'a.id=b.temp_id')
					->where(['b.state'=>1,'b.sid'=>__SID__,'b.lang'=>__LANG__])->asArray()->one();
					if(empty($r)){
						$r = static::find()
						->select(['a.*'])
						->from(['a'=>'{{%templetes}}'])
						->innerJoin(['b'=>'{{%temp_to_shop}}'],'a.id=b.temp_id')
						->where(['b.state'=>1,'b.sid'=>__SID__])->asArray()->one();
					}
				}
			}
			$config['templete'][$c][__LANG__] = $r;	
			Yii::$app->session->set('config', $config);
			return $r;
		}
	}
	
	public function getConfigs($code = false, $lang = __LANG__,$sid=__SID__,$cached=true){
		return Yii::$app->idb-> getConfigs($code, $lang, $sid, $cached);
	}
	
	public static function findByUrl($url = ''){
		return static::find()->where(['url'=>$url,'sid'=>__SID__])->asArray()->one();
	}
	
	private function setHttpsMethod(){
		if(isset(Yii::$site['seo']['ssl'])){
			if(isset(Yii::$site['seo']['ssl'][DOMAIN_NOT_WWW])  && Yii::$site['seo']['ssl'][DOMAIN_NOT_WWW] == 'on'){
				
				if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
					if(strpos(DOMAIN, 'beta') !== false){
						return true;
					}
					$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
					header('Location: ' .$redirect, true, 301);
					exit;
				}
				return true;
				
			}else{
				if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on"){
					$redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
					header('Location: ' . $redirect,true , 301);
					exit;
				}
				return false;	
			}
		}else{
		if((isset(Yii::$site['other_setting'][DOMAIN.'_ssl']) && cbool(Yii::$site['other_setting'][DOMAIN.'_ssl']) == 1) ||				
			(isset(Yii::$site['other_setting']['ssl']) && cbool(Yii::$site['other_setting']['ssl']) == 1)){
			if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
				if(strpos(DOMAIN, 'beta') !== false){
					return true;
				}
				$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];									
				header('Location: ' .$redirect, true, 301);
				exit;
			}
			return true;
		}else{
			if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on"){
				$redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header('Location: ' . $redirect,true , 301);
				exit;
			}
			return false;			
		}
		}
	}
	
	private function redirect301(){		 
		// check redirect domain
		$rule = '^' . DOMAIN;
		$r = (new \yii\db\Query())->from('redirects')->where(['rule'=>$rule,'is_active'=>1,'sid'=>__SID__])->one();
		if(!empty($r) && $r['target'] != "" && $r['target'] != $rule){
			$url = SCHEME . '://' . substr($r['target'], 1) . URL_PORT . URL_PATH;
			header('Location: ' . $url,true,$r['code']);
			exit;
		}
		
		if(!empty($this->slug)){			
			$s = json_decode($this->slug['redirect'],1);
			if(isset($s['target']) && $s['target'] != ""){				
				header('Location: ' . $s['target'],true,$s['code']);
				exit;				
			}else{
				$r = (new \yii\db\Query())->from('redirects')->where(['rule'=>[$this->slug['url'],FULL_URL],'is_active'=>1,'sid'=>__SID__])->one();
				if(!empty($r) && $r['target'] != ""){					
					header('Location: ' . $r['target'], true,$r['code']);exit;
				}
			}
		}
		else{
			$rule = __DETAIL_URL__ == '' ? '@' : __DETAIL_URL__;
			$r = (new \yii\db\Query())->from('redirects')->where(['rule'=>[$rule,FULL_URL],'is_active'=>1,'sid'=>__SID__])->one(); 
			if(!empty($r) && $r['target'] != ""){
				header('Location: ' . $r['target'],true,$r['code']);
				exit;
			}
			
		}		 
	}
	
	private function setDefaultLanguage(){
		/**
		 * Set language
		 *
		 */
		$config = Yii::$app->session->get('config');
		defined('ROOT_LANG') or define("ROOT_LANG",'vi_VN');
		defined('SYSTEM_LANG') or define("SYSTEM_LANG",ROOT_LANG);
		defined('ADMIN_LANG') or define("ADMIN_LANG",SYSTEM_LANG);
		if(defined('__URL_LANG__')){
			defined('__LANG__') or define("__LANG__",__URL_LANG__);
			defined('DEFAULT_LANG') or define("DEFAULT_LANG",__URL_LANG__);
		}else{
			if(!isset($config['language'])){
				$default_lang = [];//\app\modules\admin\models\AdLanguage::getUserDefaultLanguage();
				if(empty($default_lang)){
					$default_lang = ['code'=>'vi_VN','name'=>'Tiếng Việt','country_code'=>'vn'];
				}
				$language = ['language'=>$default_lang,'default_language'=>$default_lang];
				$config = $language;
				Yii::$app->session->set('config', $config);
			}else{
				//$config = $language;
			}
			defined('__LANG__') or define("__LANG__",(isset($config['language']['code']) ? $config['language']['code'] : 'vi_VN'));
			defined('DEFAULT_LANG') or define("DEFAULT_LANG", isset($config['default_language']['code']) ? $config['default_language']['code'] : SYSTEM_LANG);
				
		}
		return __LANG__;
	}
	
}
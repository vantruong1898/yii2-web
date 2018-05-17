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
class Router extends \yii\base\Component
{
	/*
	
	*/
	public $settings = [], $slug = [],$allowController = [
			'index',
			'default',
			'ajax',
			'sajax',
			'login',
			'logout',
			'error',
			'forgot'
	];
	public $_adminRoute = ['admin','acp','apc','cpanel'], $defaultRoute = 'site';
	private $_router = '';
	protected $request;		 	 
	
	public function init(){
		$this->bootstrap();
	}
	protected function bootstrap(){
		$this->request = Yii::$app->request;
		/**
		 * Phân tích dữ liệu từ server header
		 * 
		 */
		Yii::setAlias('@themes', Yii::getAlias('@webroot/themes'));
		Yii::setAlias('@libs', Yii::getAlias('@web/libs'));
		
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
				'__TIME__'=>time(),
				'DS' => '/'
		);
		foreach($d as $k=>$v){
			defined($k) or define($k,$v);
		}
		// Lấy thông tin shop từ domain đang chạy 		 
		$r = \izi\web\Shop::getDomainInfo();		 	
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
		
		$this->settings = Yii::$app->idb->getConfigs('SETTINGS',false,__SID__,false);
		
		if(!isset($this->settings['currency']['default'])){
			Yii::$app->c->setDefaultCurrency(1);
			$this->settings = Yii::$app->idb->getConfigs('SETTINGS',false,__SID__,false);
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
									'pattern'=>'<module:\w+>/',
									'route'=>'<module:\w+>/',
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
	 
		if(__IS_ADMIN__){
			require_once Yii::getAlias('@common') . '/functions/admin_function.php';
		}
		
		defined("CBASE_URL") or define('CBASE_URL', __IS_ADMIN__ ? ADMIN_ADDRESS : SITE_ADDRESS);
		
		if(!empty($this->_router) && !__IS_ADMIN__){
			
			if(!in_array($this->_router[0], ['tag','tags'])){
				foreach ($r = array_reverse($this->_router) as $url){
					$s = \izi\web\Slug::findUrl($url);					 
					if(!empty($s)){
						$this->slug = $s;						 
						
						break;
					}else{
	
					}
				}
			}
			
		}
		 
		$pos = strpos(Yii::$app->request->url, '.');		
		
		if($pos !== false){
			$url = substr($url, 0,$pos);
		}
		
		/**
		 * Lay lang theo url 
		 */					
		
		if(__IS_ADMIN__){
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
				\izi\web\Slug::setRedirect($this->slug); 
			}
			
		}
		Yii::$app->l->setDefaultLanguage();
		/**
		 * 
		 */
 
		\izi\web\Slug::validateSlug($this->slug);		 
		
		if(isset($this->slug['checksum']) && $this->slug['checksum'] != ""
				&& $this->slug['checksum'] != md5(URL_PATH)){
					// báo link sai
					$url1 = \izi\web\Slug::getUrl($s['url']);
					if(md5($url1) == $s['checksum']){
						Yii::$app->getResponse()->redirect($url1,301);
					}else{
						//$url = 'error';
					}
		}
 		$this->setHttpsMethod();
 		if(__IS_ADMIN__){
 			defined('ADMIN_VERSION') or define('ADMIN_VERSION', $this->getAdminVersionCode()) ;
 		}
 		define ('__DEFAULT_MODULE__',$this->defaultRoute);
 		$this->setDetailUrl();
	}
		 
	public function setDetailUrl(){
		$r = $this->slug;
		//$check_database = false;
		$private_temp = 0;
		$controller_style = 0;
		/**
		 * 
		 */
		$is_detail = false; 
		if(strlen(__DETAIL_URL__)>0 && !__IS_SUSPENDED__ && !empty($r)){
			$pos = strpos($r['route'], '/');
			if($pos !== false){
				$this->defaultRoute = substr($r['route'], 0,$pos);
			}
			// Set route[0]
			$this->_router[0] = $r['route'];
			
			// 
			if(__IS_ADMIN__){
				if($this->slug['hasChild']){
					// Set
				}
				define('CONTROLLER_CODE', $r['child_code']);
			}else{
				define('__ITEM_ID__', $r['item_id']);
				define('__ITEM_TYPE__', $r['item_type']);
				
				$seo = [];
				switch (__ITEM_TYPE__){
					case 1: // Article
						$is_detail = true;
						$item = \izi\web\Shop::getArticleDetail(__ITEM_ID__);
						$r = \izi\web\Slug::getItemCategory(__ITEM_ID__);
						//
						Yii::$app->s->item = $item;
						//
						if(isset($item['temp_id']) && $item['temp_id']>0){
							$private_temp = $item['temp_id'];
						}
						//
						if(isset($item['style']) && $item['style']>0){
							$controller_style = $item['style'];
						}
						$root = \izi\web\Slug::getRootItem($r);
						
						// Set seo config
						$seo['title'] = isset($item['seo']['title']) && $item['seo']['title'] != "" ? $item['seo']['title'] : $item['title'];
						$seo['description'] = isset($item['seo']['description']) && $item['seo']['description'] != "" ?
						$item['seo']['description'] : (isset($item['info']) && $item['info'] != "" ? $item['info'] :'');						
						$seo['keyword'] = (isset($item['seo']['focus_keyword']) && $item['seo']['focus_keyword'] != "" ?
								$item['seo']['focus_keyword'] . ',' : (isset($item['focus_keyword']) && $item['focus_keyword'] != "" ?
								$item['focus_keyword'] . ',' : '') ) . (isset($item['seo']['keyword']) && $item['seo']['keyword'] != "" ?
								$item['seo']['keyword'] :'');									
						$seo['og_image'] = isset($item['icon']) ? $item['icon'] : '';
						//\izi\web\Shop::setViewedCount(__ITEM_ID__);
						break;
					case 0: // Site Menu
						$is_detail = false;
						$r = \izi\web\Slug::getCategory(__ITEM_ID__);
						$root = \izi\web\Slug::getRootItem($r);
						
						if($r['route'] == 'manual'){
							$r['route'] = trim($r['link_target'],'/');
						}
						//
						if(isset($r['temp_id']) && $r['temp_id']>0){
							$private_temp = $r['temp_id'];
						}
						//
						if(isset($r['style']) && $r['style']>0){
							$controller_style = $r['style'];
						}
						
						// Set seo
						$seo['title'] = isset($r['seo']['title']) && $r['seo']['title'] != "" ? $r['seo']['title'] : $r['title'];
						$seo['description'] = isset($r['seo']['description']) && $r['seo']['description'] != "" ? $r['seo']['description'] :'';
						
						$seo['keyword'] = (isset($r['seo']['focus_keyword']) && $r['seo']['focus_keyword'] != "" ?
						$r['seo']['focus_keyword'] .',' : (isset($r['focus_keyword']) && $r['focus_keyword'] != "" ? $r['focus_keyword'] .',' : ''))
						. (	isset($r['seo']['keyword']) && $r['seo']['keyword'] != "" ? $r['seo']['keyword'] :'');
						$seo['og_image'] = isset($r['icon']) ? $r['icon'] : '';
						
						break;
					case 2: // Box
						$r = \izi\web\Box::getItem(__ITEM_ID__);
						if(!empty($r)){
							define('__BOX_ID__', $r['id']);							
							unset($r['id']);
							$seo['title'] = $r['title'];
						}
						break;
					case 3: // Box
						//$r = \app\modules\admin\models\Box::getItem($r['item_id']);
						//define('__IS_DETAIL__', true);
						break;
				}
				
				define('__ROOT_CATEGORY_ID__', isset($root['id']) ? $root['id'] : 0);
				define('__ROOT_CATEGORY_NAME__', isset($root['title']) ? $root['title'] : '');
				define('__ROOT_CATEGORY_URL__', isset($root['url']) ? $root['url'] : '');				
				define('CONTROLLER_CODE', $r['route']);
				
				if(!empty($seo)){
					foreach ($seo as $key=>$value){
						Yii::$app->s->config['seo'][$key] = $value;
					}
				}
				
			}
			
			if(isset($r['spc'])){
				define('__SPC_VALUE__',$r['spc']);
			}else{
				define('__SPC_VALUE__',0);
			}
			if(isset($r['lft'])){
				define('CONTROLLER_LFT', $r['lft']);
				define('CONTROLLER_RGT', $r['rgt']);
			}
			define('__CATEGORY_NAME__',isset($r['title']) ? uh($r['title']) : '');
			define('__CATEGORY_PARENT_ID__', isset($r['parent_id']) ? $r['parent_id'] : 0);
			define('__CATEGORY_ACTION_DETAIL__',isset($r['action_detail']) ? $r['action_detail'] : '');
			define('__CATEGORY_URL__', isset($r['url']) ? $r['url'] : '');
			
			//view2(__CATEGORY_NAME__,true);
			
		}elseif(__IS_SUSPENDED__){
			$this->defaultRoute = 'site';
			$this->_router = ['suspended'];
		}
		
		if(__IS_ADMIN__ && empty($this->slug) && __DETAIL_URL__ != "" && !in_array($this->_router[0], $this->allowController)){
			$this->_router = ['error'];
		}
		
		define('__IS_DETAIL__', $is_detail);
		
		defined('PRIVATE_TEMPLETE') or define('PRIVATE_TEMPLETE',$private_temp);
		
		defined('CONTROLLER_STYLE') or define('CONTROLLER_STYLE',$controller_style);
		
		defined('__ITEM_ID__') or define('__ITEM_ID__', 0);
		
		define('CONTROLLER_ID', isset($r['id']) ? $r['id'] : -1);
		
		defined('__CATEGORY_URL__') or define('__CATEGORY_URL__', __DETAIL_URL__);
		
		
		Yii::$app->request->url = "/" . $this->defaultRoute .'/'. implode('/', $this->_router);
		define('__CATEGORY_ID__', isset($r['id']) ? $r['id'] : (in_array(Yii::$app->request->url,['/site','/site/','/site/index']) ? 0 : -1));
		if(URL_SUFFIX != ""){
			if(strrpos(Yii::$app->request->url, URL_SUFFIX) !== false){
				//$request->url = str_replace(URL_SUFFIX, '', $request->url);
			}
		}
		if(URL_SUFFIX != "" && $request->url != '/') {
			if(strpos(Yii::$app->request->url, URL_SUFFIX) === false){
				Yii::$app->request->url .= URL_SUFFIX;
			}
			$ux = explode('/', Yii::$app->request->url);
			if(count($ux)>4){
				$nx = [];
				$nx[] = $ux[0];
				$nx[] = $ux[1];
				$nx[] = $ux[2];
				$nx[] = $ux[count($ux)-1];
				$request->url = implode('/', $nx);
				
			}
		}
		
		Yii::$app->s->category = $r;		
		
		define('CHECK_PERMISSION', isset($r['is_permission']) && $r['is_permission'] == 1 ? true : false);
		 
		defined('CONTROLLER_TEXT') or define('CONTROLLER_TEXT', __DETAIL_URL__);
		defined('__RCONTROLLER__') or define('__RCONTROLLER__', __DETAIL_URL__);
		defined('__CONTROLLER__') or define('__CONTROLLER__', $this->defaultRoute);
		defined('CONTROLLER') or define('CONTROLLER', !empty($r) ? $r['route'] : 'index');
		defined('CONTROLLER_CODE') or define('CONTROLLER_CODE', !empty($r) ? $r['route'] : 'index');
		//
		define('ROOT_USER','root');
		define('DEV_USER','dev');
		define('ADMIN_USER','admin');
		define('USER','user');
		
		\izi\web\Shop::setTemplete();
		
	}
	
	public function getAdminConfig(){
		return Yii::$app->idb->getConfigs('ADMIN_CONFIGS');
	}
	
	public function getAdminVersionCode(){
		$c = $this->getAdminConfig();
		return isset($c['version']) ? $c['version'] : 'v1';
		
	}	
		 	
	/**
	 * Check HTTPS && Redirect
	 * @return boolean
	 */
	private function setHttpsMethod(){
		
		if(isset(Yii::$app->s->config['seo']['ssl'])){
			if(isset(Yii::$app->s->config['seo']['ssl'][DOMAIN_NOT_WWW])  && Yii::$app->s->config['seo']['ssl'][DOMAIN_NOT_WWW] == 'on'){				
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
		
		//
		$www = isset(Yii::$app->s->config['seo']['www']) ? Yii::$app->s->config['seo']['www'] : -1;
		if(!isset(Yii::$app->s->config['seo']['amp'])) {
			Yii::$app->s->config['seo']['amp'] = [];
		}
		switch ($www){
			case 0:
				if(strpos(ABSOLUTE_DOMAIN, 'www.') !== false){
					header('Location:' . SCHEME  . '://' . URL_NON_WWW . URL_PORT . URL_PATH ,301);
					exit;
				}
				break;
			case 1:
				if(strpos(ABSOLUTE_DOMAIN, 'www.') === false){
					header('Location:' . SCHEME  . '://www.' . URL_NON_WWW . URL_PORT . URL_PATH ,301);
					exit;
				}
				break;
		}
		//
	}
			
	 
	
}
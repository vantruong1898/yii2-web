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
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

class Controller extends \yii\web\Controller
{
	public function behaviors()
	{
		// Set header
		header('Expires:'.date("D, j M Y G:i:s ",time()));
		header('Pragma: cache');
		if(!Yii::$app->user->isGuest){
			header('Cache-Control: max-age=0, public');
		}else{
			header('Cache-Control: max-age='.(7200).', public');
		}		
		
		$this->setLayout();				
				
		return [
				'access' => [
						'class' => AccessControl::className(),
						'only' => ['logout', 'signup'],
						'rules' => [
								[
										'actions' => ['signup'],
										'allow' => true,
										'roles' => ['?'],
								],
								[
										'actions' => ['logout'],
										'allow' => true,
										'roles' => ['@'],
								],
						],
				],
				'verbs' => [
						'class' => VerbFilter::className(),
						'actions' => [
								'logout' => ['post'],
						],
				],
		];
	}
	
	
	public function setLayout(){		
		defined('__VIEW_PATH__') or define ('__VIEW_PATH__',Yii::$app->viewPath . '/' . $this->id);		
		// Change layout amp & api
		if(__IS_DETAIL__ && in_array($this->action->id, Yii::$app->s->config['seo']['amp'])){
			Yii::$app->s->hasAmp = true;
			if(Yii::$app->request->get('layout') == 'amp'){
				Yii::$app->s->ampLayout = true;
				$this->layout = 'amp';
			}
		}
		
		if(substr(DOMAIN,0,4) == 'api.'){
			$this->layout = 'api';
			$this->is_api = true;
			Yii::$app->s->is_api = true;
		}
		defined('__ACTION__') or define('__ACTION__',$this->action->id);
		
		if(__ACTION__ == 'cart' && Yii::$app->request->get('view') == 'checkout'){
			$this->layout = 'checkout';
		}elseif (__ACTION__ == 'cart'){
			$this->layout = 'cart';
		}
		
		
		$defaultViewPath = $this->viewPath;
		$viewPath = $defaultViewPath . '/' . __TEMP_NAME__;
		$action = $this->action->id;				
		
		switch ($action){
			case 'manual':
				if(isset(Yii::$app->s->category['link_target']) && Yii::$app->s->category['link_target'] != ""){
					$fp = $viewPath. '/' . Yii::$app->s->category['link_target'] . '.php';
					if(!file_exists($fp)){
						$fp = $viewPath . '/' .$action. '.php';
						if(file_exists($fp)){
							
						}else{
							$action =  'index';
						}
					}else{
						$action = Yii::$app->s->category['link_target'] ;
					}
				}else{
					$fp = $viewPath . '/' .$action. '.php';
					if(file_exists($fp)){
						
					}else{
						$action= 'index';
					}
				}
				break;
		}
		
		
		// Set viewpath
		
		$filename = $defaultViewPath. '/' . __TEMP_NAME__ . '/' .$action . '.php';
		if(file_exists($filename)){
			$this->viewPath .= '/' . __TEMP_NAME__;
		}
		
		//			
		
		$__VIEWS__ = $defaultViewPath . '/' . __TEMP_NAME__;
		
		defined('__IS_SEARCH__') or define('__IS_SEARCH__', $action == 'search' ? true : false);
		if(__IS_SEARCH__){
			defined('__CATEGORY_NAME__') or define('__CATEGORY_NAME__', Yii::$app->t->translate ('label_search_result'));
		}
		// Check view path for device
		if(	//__IS_MOBILE_TEMPLETE__ &&
				!in_array($action, ['sajax'])){
					$device = Yii::$app->s->ampLayout ? 'amp' : \izi\web\Shop::$device;
					$filename = $this->viewPath . '/' . $device . '/' .$action . '.php';
					if(file_exists($filename)){
						$this->viewPath .= '/' . $device;
						$__VIEWS__ .= '/' . $device;
					}
		}
		
		define('__VIEWS__', $__VIEWS__);
		define('__VIEWS_PATH__', __VIEWS__);
		
		$fp = Yii::getAlias('@app/views/layouts/'.__TEMP_NAME__.'.php');
		if(file_exists($fp)) $this->layout = __TEMP_NAME__;
				
	}
	
	public function actions(){	
		return [
				'error' => [
						'class' => 'izi\web\ErrorAction',
				],
				'captcha' => [
						'class' => 'yii\captcha\CaptchaAction',
						'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
				],
		];
	}
	
	public function renderTemplete(){
		if(isset(Yii::$app->s->category['temp_code']) && Yii::$app->s->category['temp_code'] != ""){
			$temp_code = Yii::$app->s->category['temp_code'];
		}else{
			$temp_code = $this->action->id;
		}
		//
		
		//
		if(__IS_DETAIL__){
			$fp = $this->viewPath . '/' . $temp_code . '_detail.php';
			if(!file_exists($fp)){
				$fp = $this->viewPath . '/' . $this->action->id . '_detail.php';
				if(!file_exists($fp)){
					$fp = $this->viewPath . '/' . $temp_code . '.php';
					if(!file_exists($fp)){
						$temp = $this->action->id;
					}else{
						$temp = $temp_code;
					}
				}else{
					$temp = $this->action->id . '_detail';
				}
			}else{
				$temp = $temp_code . '_detail';
			}
		}else{
			$fp = $this->viewPath . '/' .$temp_code . '.php';
			if(!file_exists($fp)){
				$temp = $this->action->id;
			}else{
				$temp = $temp_code;
			}
		}
		return $this->render($temp);
	}
	
	/**
	 * Set action
	 * @return string
	 */
	
	public function actionError404(){		
		return $this->render('error',[
				'name'=>'Không tìm thấy trang.'
		]);
	}
	
	public function actionNews(){
		return $this->render($this->action->id);
	}
	
	public function actionIndex(){
		return $this->render($this->action->id);
	}
	
	public function actionText(){
		return $this->render($this->action->id);
	}
	
	public function actionProducts(){
		return $this->render($this->action->id);
	}
	
	public function actionContact(){
		return $this->render($this->action->id);
	}
	
	public function actionSitemap(){
		header('Content-type: text/xml');
		$s = Yii::$app->idb->getConfigs('SITEMAP');
		if(isset($s[DOMAIN_NOT_WWW])){
			echo uh($s[DOMAIN_NOT_WWW]);
		}else{
			//echo get_site_value('seo/sitemap');
		}
		exit;
		
	}
	
	
	public function actionRobots(){
		header('Content-type: text/plain');
		$s = Yii::$app->idb->getConfigs('ROBOTS');
		if(isset($s[DOMAIN_NOT_WWW])){
			echo uh($s[DOMAIN_NOT_WWW]);
		}else{
			//echo get_site_value('seo/sitemap');
		}
		exit;
	}
	
	public function actionGetapi(){
		return $this->render('api');
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
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
class Language extends \yii\db\ActiveRecord
{
	public $key = 'LANGUAGE';
	
	public static function tableName(){
		return '{{%site_configs}}';
	}
	
	public static function tableLanguage(){
		return '{{%ad_languages}}';
	}
	
	/**
	  * Begin
	  **/
	
	
	public function __construct(){
		
	}
	
	public function getItem($id=0,$o=[]){
		$item = static::find()
		->from(self::tableLanguage())
		->where(['id'=>$id ]);
		
		$item = $item->asArray()->one();
		
		return $item;
	}
	
	
	public function getItemByCode($code,$o=[]){
		$item = static::find()
		->from(self::tableLanguage())
		->where(['code'=>$code ]);
		
		$item = $item->asArray()->one();
		
		return $item;
	}
	
	/**
	 * Get language from DB
	 */
	private function dbGetItem($id){
		if($id > 0){
			$item = static::find()
			->from($this->tableLanguage())
			->where(['id'=>$id]);		
			return $item->asArray()->one();
		}else{
			return $this->dbGetItemByCode($id);
		}
	}
	
	private function dbGetItemByCode($code){
		if(is_numeric($code) && $code > 0){
			return $this->dbGetItem($id);			
		}else{
			$item = static::find()
			->from($this->tableLanguage())
			->where(['code'=>$code]);
			return $item->asArray()->one();
		}
	}
	
	/**
	 * Lấy danh sách ngôn ngữ
	 */
	public function getList($o = []){
		$r = Yii::$app->idb->getConfigs($this->key,false,__SID__,false);
		if(empty($r)){
			$lang = $this->dbGetItemByCode(ROOT_LANG);
			$lang['root_active'] = 1;
			$lang['is_active'] = 1;
			$lang['is_default'] = $lang['default'] = 1;
			$lang['domain'] = '';
			Yii::$app->idb->updateBizData([$lang],[
					'code' => $this->key,
					'sid' => __SID__,
			]);
			$r[0] = $lang;
		}
		
		if(isset($o['is_active']) && !empty($r)){
			foreach ($r as $k=>$v){
				if(isset($v['is_active']) && $v['is_active'] == $o['is_active']){}else{unset($r[$k]);}
			}
		}
		
		if(!empty($r) && isset($o['translate']) && $o['translate']){
			foreach ($r as $k => $v){
				$r[$k]['title'] =
				Yii::$app->t->translate($v['lang_code'], __IS_ADMIN__ ? ADMIN_LANG : __LANG__)
				. ' - ' . $v['title'];
				;
			}
		}
		
		return $r;
	}
	
	/**
	 * Lấy ngôn ngữ mặc định của trang
	 * @return array
	 */
	public function getUserDefaultLanguage(){
		
	}
	
	public function setDefaultLanguage(){
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
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
class Shop extends \yii\db\ActiveRecord
{
	/**
	 * Configs
	 * @return string
	 */
	public $config, $setting;
	
	public function __construct(){
		$this->config = Yii::$app->idb->getConfigs();
		$this->setSeoConfig();
		$this->setting = Yii::$app->params['settings'];		
	}
	
	public static function tableName(){
		return '{{%shops}}';
	}
	
	public static function tableDomain(){
		return '{{%domain_pointer}}';
	}
	
	public static function getDomainInfo($domain = __DOMAIN__){
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
	
	/**
	 * Get seo config 
	 * @return array
	 */
	public function setSeoConfig(){		
		$seo = Yii::$app->idb->getConfigs('SEO');
		if(isset($seo['domain_type']) && $seo['domain_type'] == 'multiple'){
			$domains = isset($seo['domain']) && $seo['domain'] != '' ? explode(',', $seo['domain']) : [];
			$sd = [];
			if(!empty($domains)){
				foreach ($domains as $domain){
					if($domain == DOMAIN){
						if(isset($seo[$domain])){
							$sd = $seo[$domain];
							unset($seo[$domain]);
						}
					}else{
						if(isset($seo[$domain])){
							unset($seo[$domain]);
						}
					}
				}
			}
			$this->config['seo'] = array_merge($sd,$seo);
			unset($seo);
		}elseif (isset($seo['domain_type'])){
			$this->config['seo'] = $seo;unset($seo);
		}
		$www = isset($this->config['seo']['www']) ? $this->config['seo']['www'] : -1;
		if(!isset($this->config['seo']['amp'])) {
			$this->config['seo']['amp'] = [];
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
		return $this->config['seo'];
	}
}
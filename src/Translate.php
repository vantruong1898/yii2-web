<?php
namespace izi\web;

use Yii;
use yii\db\Query;
class Translate extends \yii\base\Component
{
	public $lang = __LANG__;
	public $t;
	public function tableName(){
		return '{{%text_translate}}';
	}
	public function tableUserTranslate(){
		return '{{%user_text_translate}}';
	}
	public function __construct(){
		$this->t[$this->lang] = $this->loadJson($this->lang);
		if(__IS_ADMIN__ && ADMIN_LANG != $this->lang){
			$this->t[ADMIN_LANG] = $this->loadJson(ADMIN_LANG);
		}
	}
	
	public function updateUserData(){
		$l = (new Query())->from($this->tableUserTranslate())->where(['sid'=>__SID__])
		->andWhere(['not in','lang_code',$this->loadJsonLangCode()])
		->all();
		if(!empty($l)){
			foreach ($l as $v){
				$this->upadteLangcode($v['lang_code'], $v['lang'], $v['value']);
			}
		}
	}
	
	
	public function loadJsonLangCode($lang = __LANG__){
		$filename = Yii::getAlias('@app') . '/i18n/' . __SID__ . "/${lang}.json";
		if(file_exists($filename)){
			$text = file_get_contents($filename);
			$text = json_decode($text,1);
		}else{
			$text = [];
			writeFile($filename,json_encode($text));
		}
		$r = [];
		if(!empty($text)){
			foreach ($text as $lang_code => $name){
				$r[] = $lang_code;
			}
		}
		return $r;
	}


	public function loadJson($lang = __LANG__){
		$filename = Yii::getAlias('@app') . '/i18n/' . __SID__ . "/${lang}.json";
		if(file_exists($filename)){
			$text = file_get_contents($filename);
			$text = json_decode($text,1);
		}else{
			$text = [];
			writeFile($filename,json_encode($text));
		}
		return $text;
	}
	public function translate($lang_code, $lang = __LANG__, $params = []){ 
		if(!isset($this->t[$lang])){
			$this->t[$lang] = $this->loadJson($lang);
		}
		//
		$default = isset($params['default']) ? $params['default'] : $lang_code;
		$getdb = isset($params['getdb']) && !$params['getdb'] ? false : true;
		//
		$text = $lang_code;
		if(!isset($this->t[$lang][$lang_code])){
			$text = '';
			if(!$getdb){
				return $text;
			}
			$t = (new Query())->from($this->tableUserTranslate())->where([
					'lang_code'=>$lang_code,'lang'=>$lang,'sid'=>__SID__
			])->one();
		
			if(empty($t)){
				$t = (new Query())->from($this->tableName())->where([
						'lang_code'=>$lang_code,'lang'=>$lang
				])->one();
			}
			
			if(!empty($t)){
				$text = $this->t[$lang][$lang_code] = $t['value'];
				$filename = Yii::getAlias('@app') . '/i18n/' . __SID__ . "/${lang}.json";
				writeFile($filename,json_encode($this->t[$lang]));
			}else{
				// Thêm vào bảng
				//$id = \app\modules\admin\models\TextTranslate::getID();
				/*
				Yii::$app->db->createCommand()->insert(\app\modules\admin\models\TextTranslate::tableName(),[
						'id' => $id,
						'lang_code' => $lang_code,
						'lang' => $lang,
						'value' => $default,
				])->execute();
				*/
				if($default != $lang_code){
					Yii::$app->db->createCommand()->insert($this->tableUserTranslate(),[
							'sid' => __SID__,
							'lang_code' => $lang_code,
							'lang' => $lang,
							'value' => $default == $lang_code ? '' : $default,
					])->execute(); 
				}
				// 
				return $default;
			}
		}else{
			$text = $this->t[$lang][$lang_code];
		}
		if($text == $lang_code){
			$text = $default;
		}
		return $text;
	}
	
	////
	public function updateData($data, $lang){
		$filename = Yii::getAlias('@app') . '/i18n/' . __SID__ . "/${lang}.json";		
		writeFile($filename,json_encode($data));
	}
	
	public function deleteLangcode($lang_code, $lang = false){
		$language = (\app\modules\admin\models\AdLanguage::getUserLanguage());
		if(!empty($language)){
			foreach ($language as $v){
				if($lang === false){
					$data = $this->loadJson($v['code']);
					if(isset($data[$lang_code])){
						unset($data[$lang_code]);
						$this->updateData($data, $v['code']);
					}
				}elseif($lang == $v['code']){
					$data = $this->loadJson($v['code']);
					if(isset($data[$lang_code])){
						unset($data[$lang_code]);
						$this->updateData($data, $v['code']);
					}
					break;
				}
			}
		}
	}
	public function upadteLangcode($lang_code, $lang, $value){
		$data = $this->loadJson($lang);
		$data[$lang_code] = $value;
		$this->updateData($data, $lang);
	}
	
	
	
}
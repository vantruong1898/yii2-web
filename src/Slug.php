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
class Slug extends \yii\db\ActiveRecord
{
	
	public static function tableName(){
		return '{{%slugs}}';
	}
	
	
	public static function tableSiteMenu(){
		return '{{%site_menu}}';
	}
	
	public static function tableItemToCategory(){
		return '{{%items_to_category}}';
	}
	public static function tableAdminMenu(){
		return '{{%admin_menu}}';
	}
	
	
	public static function tableArticle(){
		return '{{%articles}}';
	}
	
	public static function tableRedirect(){
		return '{{%redirects}}';
	}
	
	
	public function __construct(){
		
	}
	
	public static function findUrl($url = ''){
		return static::find()->where(['url'=>$url,'sid'=>__SID__])->asArray()->one();
	}
	
	
	public static function setRedirect($slug){
		// check redirect domain
		$rule = '^' . DOMAIN;
		$r = (new \yii\db\Query())->from(self::tableRedirect())->where(['rule'=>$rule,'is_active'=>1,'sid'=>__SID__])->one();
		if(!empty($r) && $r['target'] != "" && $r['target'] != $rule){
			$url = SCHEME . '://' . substr($r['target'], 1) . URL_PORT . URL_PATH;
			header('Location: ' . $url,true,$r['code']);
			exit;
		}
		
		if(!empty($slug)){
			$s = json_decode($slug['redirect'],1);
			if(isset($s['target']) && $s['target'] != ""){
				header('Location: ' . $s['target'],true,$s['code']);
				exit;
			}else{
				$r = (new \yii\db\Query())->from(self::tableRedirect())->where(['rule'=>[$slug['url'],FULL_URL],'is_active'=>1,'sid'=>__SID__])->one();
				if(!empty($r) && $r['target'] != ""){
					header('Location: ' . $r['target'], true,$r['code']);exit;
				}
			}
		}
		else{
			$rule = __DETAIL_URL__ == '' ? '@' : __DETAIL_URL__;
			$r = (new \yii\db\Query())->from(self::tableRedirect())->where(['rule'=>[$rule,FULL_URL],'is_active'=>1,'sid'=>__SID__])->one();
			if(!empty($r) && $r['target'] != ""){
				header('Location: ' . $r['target'],true,$r['code']);
				exit;
			}
			
		}
	}
	/**
	 * 
	 */
	
	public function getAll(){
		$query = static::find()
		->from(['a'=>$this->tableName()])
		->where(['a.sid'=>__SID__])
		->andWhere(['>','a.state',-2]);
		return $query->asArray()->all();
	}
	
	public function getItem($url = '', $item_id = 0,$item_type = 0){
		$query = (new Query())
		//->select(['route'])
		->from($this->tableName())
		->where(['sid'=>__SID__]);
		if($url != '' ){
			$query->andWhere(['url'=>$url]);
		}else{
			if($item_type == -1){
				$item_type = defined('__IS_DETAIL__') && __IS_DETAIL__ ? 1 : 0;
			}
			$query->andWhere(['item_id'=>$item_id, 'item_type'=>$item_type]);
		}
		
		return $query->one();
	}
	
	public function getRoute($url = '', $item_id = 0,$item_type = 0){
		$query = (new Query())
		->select(['route'])
		->from($this->tableName())
		->where(['sid'=>__SID__]);
		if($url != '' ){
			$query->andWhere(['url'=>$url]);
		}else{
			if($item_type == -1){
				$item_type = defined('__IS_DETAIL__') && __IS_DETAIL__ ? 1 : 0;
			}
			$query->andWhere(['item_id'=>$item_id, 'item_type'=>$item_type]);
		}
		
		return $query->scalar();
	}
	/*
	 *
	 */
	public static function getAllParent($id = 0,$inc = true){
		$item = (new Query())->from(['site_menu'])->where(['id'=>$id])->one();
		if(!empty($item)){
			$query = static::find()->from([$this->tableSiteMenu()])->select(['*'])->where([
					'<=','lft',$item['lft']
			])->andWhere([
					'>=','rgt',$item['rgt']
			])->andWhere(['sid'=>__SID__]);
			if(!$inc){
				$query->andWhere(['not in','id',$id]);
			}
			return $query->orderBy(['lft'=>SORT_ASC])->asArray()->all();
		}
		return false;
	}
	
	
	public function getRealUrl($url, $o = []){
		//
		$domain = isset($o['domain']) ? $o['domain'] : '';
		$absolute = isset($o['absolute']) && $o['absolute'] ? true : false;
		$url_type = isset($o['url_type']) ? $o['url_type'] : (isset(Yii::$app->s->setting['url_manager']['type']) ? Yii::$app->s->setting['url_manager']['type'] : (
				isset(Yii::$app->s->config['seo']['url_config']['type']) ?  Yii::$app->s->config['seo']['url_config']['type'] : 2
				));
		//
		switch ($url_type){
			case 2:
				return \yii\helpers\Url::to(["/$url"],$absolute);
				break;
		}
		//
		$item = self::getItem($url);
		if(!empty($item)){
			switch ($url_type){
				case 3: // 1 dm cha
					switch ($item['item_type']){
						case 0: // Menu
							return \yii\helpers\Url::to(["/$url"],$absolute);
							break;
						case 1: //
							$category = \app\modules\admin\models\Content::getItemCategory($item['item_id']);
							if(!empty($category)){
								$url = $category['url'] . "/$url";
							}
							return \yii\helpers\Url::to(["/$url"],$absolute);
							break;
						default:
							return \yii\helpers\Url::to(["/$url"],$absolute);
							break;
					}
					break;
				case 1: // Full
					switch ($item['item_type']){
						case 0: // menu
							$categorys = self::getAllParent($item['item_id'],false);
							$x = '';
							if(!empty($categorys)){
								foreach ($categorys as $category){
									$x .= $category['url'] . '/';
								}
							}
							$url = $x . $url;
							return \yii\helpers\Url::to(["/$url"],$absolute);
							break;
						case 1:
							$category = \app\modules\admin\models\Content::getItemCategory($item['item_id']);
							$categorys = self::getAllParent($category['id'],true);
							$x = '';
							if(!empty($categorys)){
								foreach ($categorys as $category){
									$x .= $category['url'] . '/';
								}
							}
							$url = $x . $url;
							return \yii\helpers\Url::to(["/$url"],$absolute);
							break;
					}
					
					
					break;
			}
			
		}
		return \yii\helpers\Url::to(["/$url"],$absolute);
	}
	
	
	public function getDirectLink($url, $item_id, $item_type,$domain = ''){
		switch ($item_type){
			case 0: // menu
				$tables = $this->tableSiteMenu();
				break;
			case 1: // bai viết
				$tables = $this->tableArticle();
				break;
			default:
				if(!(substr($url, 0,1) == '/')){
					$url = '/' . $url;
				}
				return Yii::$app->zii->getDomain($domain) . $url;
				break;
		}
		$c = (new Query())->from($tables)->select('url_link')->where(['id'=>$item_id])->one();
		//view($c);
		$url = isset($c['url_link']) ? $c['url_link'] : $url;
		
		if(strpos($url, '://')>0){
			return $url;
		}
		if(!(substr($url, 0,1) == '/')){
			$url = '/' . $url;
		}
		return Yii::$app->zii->getDomain($domain) . $url ;
	}
	
	
	public function getDomain($domain = ''){
		$s = Yii::$app->s->config['seo'];
		if($domain == ''){
			$domains = explode(',', isset($s['domain']) ? $s['domain'] : DOMAIN);
			$d = $domains[0];
		}else {
			$d = $domain;
		}
		
		if(strpos($d, '://') === false){
			if(SCHEME == 'http' && isset($s['ssl'][$d]) && $s['ssl'][$d] =='on'){
				$scheme = 'https';
			}else{
				$scheme = SCHEME;
			}
			$d = $scheme . '://' . $d;
		}
		return $d;
	}
	
	/**
	 * Validate url
	 */
	public static function validateSlug($slug){
		if(isset($slug['checksum']) && $slug['checksum'] != ""
				&& $slug['checksum'] != md5(URL_PATH)){
			// báo link sai & chuyển về link mới
			$url1 = self::getUrl($slug['url']);
			if(md5($url1) == $slug['checksum']){
				Yii::$app->getResponse()->redirect($url1,301);
			}
		}
	}
	
	/**
	CKC ^^
	*/
	public static function getUrl($url = '',$cate_id = 0){
		$url_link = '';
		
		$item = static::find()->where(['url'=>$url,'sid'=>__SID__])->andWhere(['>','state',-2])->one();
				
		$url_type = isset(Yii::$app->s->config['seo']['url_config']['type']) ? Yii::$app->s->config['seo']['url_config']['type'] : 2;			
		
		//
		$url_type = 3;
		//
		if($url_type == 2){
			return \yii\helpers\Url::to(['/'.$url]);
		}
		if(!empty($item)){
			if($item['item_type'] == 0) {// menu
				$item_id = $item['item_id'];
			}else{
				$item_id = $cate_id > 0 ? $cate_id : static::find()->select('category_id')->from(self::tableItemToCategory())->where(['item_id'=>$item['item_id']])->scalar();
			}
			//					
			switch ($url_type){
				case 1: // Full
					$c = [];
					foreach (\app\models\Slugs::getAllParent($item_id) as $k=>$v){
						//view($v['url']);
						$c[] = $v['url'];
					}
					if($item['item_type'] == 1) {
						$c[] = $url;
					}
					return \yii\helpers\Url::to([DS . implode('/', $c)]);
					break;
				case 3: // 1 cate
					$c = [static::find()->select('url')->from(self::tableSiteMenu())->where(['id'=>$item_id])->scalar()];
					if($item['item_type'] == 1) {
						$c[] = $url;
					}
					return \yii\helpers\Url::to(['/' . implode('/', $c)]);
					break;
				default:
					return \yii\helpers\Url::to(['/'. $item['url']]);
					break;
			}
			
			
		}else{
			return \yii\helpers\Url::to([DS. $url]);
		}
		
	}
	
}
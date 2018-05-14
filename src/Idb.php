<?php
namespace izi\web;
use Yii;
use yii\db\Query;
/**
 * Cookie represents information related with a cookie, such as [[name]], [[value]], [[domain]], etc.
 *
 * For more details and usage information on Cookie, see the [guide article on handling cookies](guide:runtime-sessions-cookies).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Idb extends \yii\db\Connection
{
	
	
	
	public function getConfigs($code = false, $lang = __LANG__,$sid=__SID__,$cached=true){
		$langx = $lang == false ? 'all' : $lang;
		$code = $code !== false ? $code : 'SITE_CONFIGS';
		$config = Yii::$app->session->get('config');
		if($cached && !isset($config['adLogin']) && isset($config['preload'][$code][$langx])
				&& !empty($config['preload'][$code][$langx])){
					return $config['preload'][$code][$langx];
		}
		//
		$query = (new Query())->select(['a.bizrule'])->from(['a'=>'{{%site_configs}}'])
		->where(['a.code'=>$code]);
		if($sid>0){
			$query->andWhere(['a.sid'=>$sid]);
		}
		if($lang !== false){
			$query->andWhere(['a.lang'=>$lang]);
		}
		$j = $query->scalar();
		if($code == 'VERSION'){
			
		}
		//view2($j);
		$l = djson($j,true);
		$config['preload'][$code][$langx] = $l;
		Yii::$app->session->set('config', $config);
		return $l;
	}
	
	public function updateBizrule($table , $data , $condition){
		
		$b = (new Query())->select('bizrule')->from(['a'=>$table])->where($condition)->one();
		if(isset($b['bizrule']) && $b['bizrule'] != ""){
			$b = json_decode($b['bizrule'],1);
		}
		if(count($b) == 1 && isset($b['bizrule'])){
			$b = [];
		}
		
		if(is_array($data)){
			if(!empty($data)){
				foreach ($data as $k=>$v){
					$b[$k] = $v;
				}
			}
			if((new Query())->from($table)->where($condition)->count(1) == 0){
				$condition['bizrule']=json_encode($b);
				return Yii::$app->db->createCommand()->insert($table,$condition)->execute();
			}
			//view($b);
			return Yii::$app->db->createCommand()->update($table,['bizrule'=>json_encode($b)],$condition)->execute();
		}
	}
	
	
	public function updateBizData($biz = [],$con = [], $replace = false){
		$table = '{{%site_configs}}';
		$b = (new Query())->select('bizrule')->from(['a'=>$table])->where($con)->one();
		
		if(isset($b['bizrule']) && $b['bizrule'] != ""){
			$b = json_decode($b['bizrule'],1);			
		}
	 
		if($replace){
			$b = [];
		}
		
		if(count($b) == 1 && isset($b['bizrule'])){
			$b = [];
		}
		if(is_array($biz)){
			if(!empty($biz)){
				foreach ($biz as $k=>$v){
					$b[$k] = $v;
				}
			}
			if((new Query())->from($table)->where($con)->count(1) == 0){
				$con['bizrule']=json_encode($b);
				return Yii::$app->db->createCommand()->insert($table,$con)->execute();
			}
			return Yii::$app->db->createCommand()->update($table,['bizrule'=>json_encode($b)],$con)->execute();
		}
	}
	
}
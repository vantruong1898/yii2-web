<?php
namespace izi\web;
use Yii;
/**
 * Cookie represents information related with a cookie, such as [[name]], [[value]], [[domain]], etc.
 *
 * For more details and usage information on Cookie, see the [guide article on handling cookies](guide:runtime-sessions-cookies).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Currencies extends \yii\db\ActiveRecord
{
	public $key = 'SETTINGS';
	public static function tableName(){
		return '{{%currency}}';
	}
	public static function tableExchangeRate(){
		return '{{%exchange_rate}}';
	}
	
	 	
	
	public function getItemByCode($code ,$o=[]){
		$query = (new \yii\db\Query())
		->from($this->tableName())
		->where(['code' => $code]);
		return $query->one();
	}
	
	
	public function getItem($id ,$cache = false){
		$query = (new \yii\db\Query())
		->from($this->tableName())
		->where(['id' => $id]);
		return $query->one();
	}
	
	public function getListCurrency($cache=false){
		$c = Yii::$app->idb->getConfigs($this->key,false,__SID__,$cache);
		$currency = isset($c['currency']['list']) ? $c['currency']['list'] : false;
		return $currency;
	}
	
	
	public function getUserCurrency(){
		
		$r = [];
		if(isset($_SESSION['config']['currency']['list']) && !empty($_SESSION['config']['currency']['list'])){
			return $_SESSION['config']['currency']['list'];
		}else{
			$l = $this->getListCurrency();
			if(!empty($l)){
				$_SESSION['config']['currency'] = $l;
			}
			return $l;
		}
		
		return [];
	}
	
	public function setDefaultCurrency($id, $cache=true){
		$c = Yii::$app->idb->getConfigs($this->key,false,__SID__,false);
		$currency = isset($c['currency']) ? $c['currency'] : false;
		if($cache && isset($currency['list']) && !empty($currency['list'])){
			foreach ($currency['list'] as $ki=>$ci){
				if($ci['id'] == $id){
					$currency['list'][$ki]['is_default'] = $ci['is_default'] = 1;
					$currency['default'] = $ci;
				}else{
					$currency['list'][$ki]['is_default'] = 0;
				}
			}
		}else{
			$v = $this->getItem($id,['cache'=>false]);
			$v['is_default'] = 1;
			$currency['list'][0] = $v;
			$currency['default'] = $v;
		}
		Yii::$app->idb->updateBizData(['currency' => $currency],[
				'code'=>$this->key, 'sid' => __SID__
		]);
		return $currency;
	}
	
	public function getCurrency($id){
		$l = $this->getUserCurrency();
		foreach ($l as $v){
			if(is_numeric($id) && $v['id'] == $id){
				return $v;
			}elseif (!is_numeric($id)){
				return $this->getCurrencyByCode($id);
			}
		}
	}
	
	public function getCurrencyByCode($code){
		$l = $this->getUserCurrency();
		foreach ($l as $v){
			if(!is_numeric($code) && $v['code'] == $code){
				return $v;
			}elseif (is_numeric($id)){
				return $this->getCurrency($id);
			}
		}
	}
	
	public function getCurrencyDecimal($id){
		$c = $this->getCurrency($id);
		if(!empty($c)){
			return $c['decimal_number'];
		}
		return 2;
	}
	
	public function getCurrencySymbol($id){
		$currency = $this->getCurrency($id);
		return $currency['symbol'];
	}
	
	public function getCurrencySymbol2($id){
		$currency = $this->getCurrency($id);
		return $currency['symbol2'];
	}
	
	public function getCurrencyLangcode($id){
		$currency = $this->getCurrency($id);
		return $currency['lang_code'];
	}
	
	public function getCurrencyName($id){
		$currency = $this->getCurrency($id);
		return $currency['name'];
	}
	
	public function getCurrencyTitle($id){
		$currency = $this->getCurrency($id);
		return $currency['title'];
	}
	
	public function getCurrencyCode($id){
		$currency = $this->getCurrency($id);
		return $currency['code'];
	}
	
	public function getCurrencyDisplayType($id){
		$currency = $this->getCurrency($id);
		return $currency['display_type'];
	}
	
	
	
	public function convertCurrency($o = []){
		$amount = isset($o['amount']) ? $o['amount'] : 0;
		$from = isset($o['from']) ? $o['from'] : 0;
		$to = isset($o['to']) ? $o['to'] : 1;
		$exchange_rate = isset($o['exchange_rate']) ? $o['exchange_rate'] : 0;
		if($from == $to) {
			$exchange_rate = 1;
		}
		if(!($exchange_rate>0)){
			$ex = $this->getExchangeRate($o);
			if(!empty($ex)){
				$exchange_rate = $ex['value'];
			}
		}
		
		return $amount * $exchange_rate;
		
	}
	
	
	public function getExchangeRate($o = []){
		$from = isset($o['from']) ? $o['from'] : 0;
		$to = isset($o['to']) ? $o['to'] : 0;
		$time = isset($o['time']) ? $o['time'] : false;
		$reverse = isset($o['reverse']) ? $o['reverse'] : false;
		$exchange_rate = isset($o['exchange_rate']) ? $o['exchange_rate'] : [];
		$from = is_numeric($from) ? $from : $this->get_id_from_code($from);
		$to = is_numeric($to) ? $to : $this->get_id_from_code($to);
		if(!is_numeric($from)){
			$c = $this->getItemByCode($from);
			$from = $c['id'];
		}
		if(!is_numeric($to)){
			$c = $this->getItemByCode($to);
			$to = $c['id'];
		}
		
		if($from == $to) return 1;
		
		if(isset($exchange_rate[$from]) && $exchange_rate[$from]>0){
			return $exchange_rate[$from];
		}
		
		$time = check_date_string($time) ? ctime(array('string'=>$time ,'return_type'=>1)) : false;
		$sql = "select a.to_currency,a.value,a.from_date from {$this->tableExchangeRate()} as a where a.from_currency=$from";
		$sql .= $to > 0 ? " and a.to_currency=$to" : "";
		$sql .= $time !== false ? " and DAYOFYEAR(a.from_date)=".date('z',$time) . " and YEAR(a.from_date)=" . date('Y',$time) : '';
		$sql .= " order by a.from_date desc";
		
		
		$sql .= " limit 1";
		$v = Yii::$app->db->createCommand($sql)->queryOne();
		
		if(!empty($v)){
			$ex = $v['value'];
			return $ex;
		}elseif(!$reverse){
			$o['from'] = $to;
			$o['to'] = $from;
			$o['reverse'] = true;
			$r = $this->getExchangeRate($o);
			if($r > 0){
				return 1/$r;
			}
		}
		return 0;
	}
	
	
	public function getAllExchangeRate($o = []){
		$from = isset($o['from']) ? $o['from'] : 0;
		$to = isset($o['to']) ? $o['to'] : 0;
		$time = isset($o['time']) ? $o['time'] : false;
		$from = is_numeric($from) ? $from : $this->get_id_from_code($from);
		$to = is_numeric($to) ? $to : $this->get_id_from_code($to);
		if(!is_numeric($from)){
			$c = $this->getItemByCode($from);
			$from = $c['id'];
		}
		if(!is_numeric($to)){
			$c = $this->getItemByCode($to);
			$to = $c['id'];
		}
		
		$time = check_date_string($time) ? ctime(array('string'=>$time ,'return_type'=>1)) : false;
		$sql = "select a.to_currency,a.value,a.from_date from {$this->tableExchangeRate()} as a where a.from_currency=$from";
		$sql .= $to > 0 ? " and a.to_currency=$to" : "";
		$sql .= $time !== false ? " and DAYOFYEAR(a.from_date)=".date('z',$time) . " and YEAR(a.from_date)=" . date('Y',$time) : '';
		$sql .= " order by a.from_date desc";
		
		//if(isset($o['return']) && $o['return'] == 'all'){
		return Yii::$app->db->createCommand($sql)->queryAll();
		//}else{
		//	$sql .= " limit 1";
		//	return Yii::$app->db->createCommand($sql)->queryOne();
		//}
	}
	
	
	public function getLastExchangeRate($o = []){
		$from = isset($o['from']) ? $o['from'] : 0;
		$to = isset($o['to']) ? $o['to'] : 0;
		$time = isset($o['time']) ? $o['time'] : false;
		$from = is_numeric($from) ? $from : $this->get_id_from_code($from);
		$to = is_numeric($to) ? $to : $this->get_id_from_code($to);
		if(!is_numeric($from)){
			$c = $this->getItemByCode($from);
			$from = $c['id'];
		}
		if(!is_numeric($to)){
			$c = $this->getItemByCode($to);
			$to = $c['id'];
		}
		
		$time = check_date_string($time) ? ctime(array('string'=>$time ,'return_type'=>1)) : false;
		$sql = "select a.to_currency,a.value,a.from_date from {$this->tableExchangeRate()} as a where a.from_currency=$from";
		$sql .= $to > 0 ? " and a.to_currency=$to" : "";
		$sql .= $time !== false ? " and DAYOFYEAR(a.from_date)=".date('z',$time) . " and YEAR(a.from_date)=" . date('Y',$time) : '';
		$sql .= " order by a.from_date desc";
		
		//if(isset($o['return']) && $o['return'] == 'all'){
		//	return Yii::$app->db->createCommand($sql)->queryAll();
		//}else{
		$sql .= " limit 1";
		return Yii::$app->db->createCommand($sql)->queryOne();
		//}
	}
}
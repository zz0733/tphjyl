<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use Think\Db;
use User\Api\UserApi as UserApi;

/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class CountController extends AdminController {

    static protected $allow = array( 'verify');

    /**
     * 后台首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
//		$dataMonth = M('count')->where()->field('left(date,7) monthName,sum(betAmount) betAmount,sum(betAmount-zjAmount) winAmount')->
//        group('monthName')->order('monthName desc')->select();
//			$this->assign('dataMonth',$dataMonth);

		$todayData = $this->getDateCount();
        $todayData['zyk'] = $todayData['betAmount']-$todayData['zjAmount']-$todayData['fanDianAmount']- $todayData['brokerageAmount'];
		$this->assign('todayData',$todayData);

		$date=strtotime("00:00")-24*3600;
		/*$yestodayData = $this->getDateCount($date);
		$this->assign('yestodayData',$yestodayData);*/

		$this->getTypes();
		$this->assign('types',$this->types);

		$this->getPlayeds();
		$this->assign('playeds',$this->playeds);

		// 当前用户统计
        $userTotal = M('members')->where('admin=0')->count();
        $this->assign('userTotal',$userTotal ? $userTotal : 0);
        $userTotalNotTest = M('members')->where(['admin=0','is_test'=>0])->count();
        $this->assign('userTotalNotTest',$userTotalNotTest ? $userTotalNotTest : 0);
        $todayTime = strtotime(date('Y-m-d',time()));
        $todayUserTotal = M('members')->where('admin=0 and regTime>='.$todayTime)->count();
        $this->assign('todayUserTotal',$todayUserTotal ? $todayUserTotal : 0);
        $todayUserTotalNotTest = M('members')->where('admin=0 and regTime>='.$todayTime.' and is_test=0')->count();
        $this->assign('todayUserTotalNotTest',$todayUserTotalNotTest ? $todayUserTotalNotTest : 0);

        $admin = M('members')->where('admin=1')->field('uid')->select();
        $adminUidArr = !$admin ? [] : array_column($admin,'uid');
        $onlineMap['isOnLine'] = ['EQ',1];
        $onlineMap['accessTime'] = ['GT',time()-900];
        if (!empty($adminUidArr)){
            $onlineMap['uid'] = ['NOT IN',$adminUidArr];
        }
        $onlineUserTotal = M('member_session')->where($onlineMap)->count();
        $this->assign('onlineUserTotal',$onlineUserTotal ? $onlineUserTotal : 0);
        $admin = M('members')->where('admin=1 or is_test=1')->field('uid')->select();
        $adminUidArr = !$admin ? [] : array_column($admin,'uid');
        $onlineMap['isOnLine'] = ['EQ',1];
        if (!empty($adminUidArr)){
            $onlineMap['uid'] = ['NOT IN',$adminUidArr];
        }
        $onlineUserTotalNotTest = M('member_session')->where($onlineMap)->count();
        $this->assign('onlineUserTotalNotTest',$onlineUserTotalNotTest ? $onlineUserTotalNotTest : 0);

		$this->meta_title = '统计概况';
        $this->display();
    }

    public function everyd(){
        $date =I('date');

        $data= $this->getDateCount($date);

        //$this->assign('dataMonth',$dataMonth);
        $this->ajaxReturn($data,'JSON');
    }
	/**
	 * 获得某天的统计信息
	 */
	private function getDateCount($date=null){
		if(!$date){
            $date=strtotime(date("Y-m-d",time()));
        }else{
		    $date = strtotime($date);
        }
//        if(I('post.searchTime')){
//            $fromTime=strtotime(I('post.searchTime'));
//            $toTime=strtotime(I('post.searchTime'))+24*3600-1;
//        }else{
//            $fromTime=strtotime(date('Y-m-d 00:00:00'));
//            $toTime=$fromTime+24*3600-1;
//        }
        $fromTime = I('fromTime');

        $toTime = I('toTime');

        if($fromTime){
            $fromTime = strtotime($fromTime);
        }else{
            $fromTime = $date;
        }
        if($toTime){
            $toTime = strtotime($toTime);
        }else{
            $toTime = $fromTime;
        }

//        exit();
        // 投注总额
		/*$map=array();
		$map['kjTime'] = array('between',array($date,$date+24*3600));
		$map['lotteryNo'] = array('neq','');
		$map['isDelete'] = 0;
		if (!empty($tUidArr)){
            $map['uid'] = ['NOT IN',$tUidArr];
        }
		$all = M('bets')->where($map)->field('IFNULL(count(*),0) betCount,IFNULL(sum(beiShu*mode*actionNum*(fpEnable+1)),0) betAmount,IFNULL(sum(bonus),0) zjAmount')->find();

		// 返点金额
		$mapF=array();
//		$map['liqType'] = array('between',array(2,3));
        $mapF['liqType'] = array('EQ',2);
        $mapF['actionTime'] = array('between',array($date,$date+24*3600));
        if (!empty($tUidArr)){
            $mapF['uid'] = ['NOT IN',$tUidArr];
        }
		$all = array_merge($all,M('coin_log')->where($mapF)->field('IFNULL(sum(coin),0) fanDianAmount')->find());*/

        $toTime = $toTime+24*3600-1;
        $map['actionTime'] = array('between',array($fromTime,$toTime));
        $map['istest'] = 0;
        $all=M('coin_log_report')->field('sum(betAmount) betAmount,
                sum(zjAmount) zjAmount,
                sum(fanDianAmount) fanDianAmount,
                sum(zyk) zyk,
                sum(rechargeAmount) rechargeAmount,
                sum(cashAmount) cashAmount,
                sum(brokerageAmount) brokerageAmount')
            ->where($map)

            ->find();


        $rechargeAmount = M('member_recharge')->alias('r')->join('__MEMBERS__ m ON m.uid = r.uid')
            ->where([
                'r.actionTime'=> ['between',[$fromTime,$toTime]],
                'm.is_test'=>0,
                '_string' => 'r.state =9 or ( r.state=1 and r.actionUid is null)',
            ])->sum('r.rechargeAmount');

        $cashAmount = M('member_cash')->where(['actionTime'=> ['between',[$fromTime,$toTime]],'state'=>0])->sum('amount');
        $all['rechargeAmount'] = $rechargeAmount;
        $all['cashAmount'] = $cashAmount;
		return $all;
	}

	public final function datelist(){
		if(IS_POST)
			$para=I('post.');
		else
			$para=I('get.');

		if (isset($para['user_type']) && $para['user_type'] == 1){
		    $whereInt = " and parentId=1";
        }else{
		    $whereInt = "";
        }
		// 时间限制
		if($para['fromTime'] && $para['toTime']){
			$fromTime=strtotime($para['fromTime']." 00:00:00");
			$toTime=strtotime($para['toTime']." 23:59:59");

			$timeWhere="and l.actionTime between $fromTime and $toTime";
		}elseif($para['fromTime']){
			$fromTime=strtotime($para['fromTime']);

			$timeWhere="and l.actionTime >= $fromTime";
		}elseif($para['toTime']){
			$toTime=strtotime($para['toTime'])+24*3600;

			$timeWhere="and l.actionTime < $toTime";
		}else{
			$toTime=strtotime('00:00');

			$timeWhere="and l.actionTime > $toTime";
		}

		// 用户限制
		$amountTitle='全部总结';
		if($para['parentId']=intval($para['parentId'])){
			// 用户ID限制
			$userWhere="and u.parentId={$para['parentId']}";
			$uid=$para['parentId'];
			$userWhere2="and concat(',', u.parents, ',') like '%,$uid,%'";
		}
		if($para['uid']=intval($para['uid'])){
			// 用户ID限制
			$user = M('members')->where(array('uid'=>$para['uid']))->find();
			$uParentId=$user['parentId'];
			$userWhere="and u.uid=$uParentId";
			$userWhere2="and concat(',', u.parents, ',') like '%,$uParentId,%'";
		}

		if($para['username']){
            $_username = trim($para['username']);
			// 用户名限制
			$userWhere="and u.username='{$_username}'";
			$user = M('members')->where(array('username'=>$_username))->find();
			$userWhere2="and concat(',', u.parents, ',') like '%,".$user['uid'].",%'";
		}

        $username = trim($para['username']);
        /*$mapT['is_test'] = ['EQ',1];
        $testUser = M('members')->where($mapT)->field('uid')->select();
        $tUidArr = empty($testUser) ? [] : array_column($testUser,'uid');
        $tUidStr = "(".implode(",",$tUidArr).")";*/

		/*$sql="select u.username, u.coin, u.uid, u.parentId, hh.fanDianAmount,hh.betAmount, hh.zjAmount,hh.cashAmount,hh.rechargeAmount,hh.brokerageAmount
		from gygy_members u ,
		(select l.uid,
		sum(case when l.liqType in (2,3) then l.coin else 0 end) as fanDianAmount,
		0-sum(case when l.liqType in (101,102,7) then l.coin else 0 end) as betAmount,
		sum(case when l.liqType=6 then l.coin else 0 end) as zjAmount,
		0-sum(case when l.liqType=107 then l.fcoin else 0 end) as cashAmount,
		sum(case when l.liqType=1 then l.coin else 0 end) as rechargeAmount,
		sum(case when l.liqType in (50,51,52,53) then l.coin else 0 end) as brokerageAmount 
		from gygy_coin_log l where 1 $timeWhere AND l.uid NOT IN $tUidStr group by l.uid) hh where hh.uid=u.uid $whereInt and 1 $userWhere group by u.uid order by (hh.zjAmount-hh.betAmount+hh.fanDianAmount) desc";*/
        $sql="select u.username, u.coin, u.uid, u.parentId, hh.fanDianAmount,hh.betAmount, hh.zjAmount,hh.cashAmount,hh.rechargeAmount,hh.brokerageAmount  
		from gygy_members u ,
		(select l.uid,
		sum(case when l.liqType = 2 then l.coin else 0 end) as fanDianAmount,
		0-sum(case when l.liqType = 101 then l.coin else 0 end) as betAmount,
		sum(case when l.liqType = 6 then l.coin else 0 end) as zjAmount,
		0-sum(case when l.liqType = 107 then l.fcoin else 0 end) as cashAmount,
		sum(case when l.liqType = 1 then l.coin else 0 end) as rechargeAmount,
		sum(case when l.liqType in (50,51,52,53) then l.coin else 0 end) as brokerageAmount 
		from gygy_coin_log l join gygy_members AS mm on l.uid=mm.uid where 1 and mm.username='{$username}' and 0 = (SELECT COUNT(1) FROM gygy_coin_log WHERE extfield0=l.extfield0 AND liqType IN (5,7)) $timeWhere/* AND l.uid NOT IN $tUidStr*/ group by l.uid limit 1) hh where hh.uid=u.uid $whereInt and 1 $userWhere group by u.uid order by (hh.zjAmount-hh.betAmount+hh.fanDianAmount) desc";
        if (isset($para['user_type']) && $para['user_type'] == 1){
            $sql = "SELECT
                        t.username,t.uid,
                        sum(coin) as coin,
                        sum(case when liqType = 2 then lcoin else 0 end) as fanDianAmount,
                        0-sum(case when liqType = 101 then lcoin else 0 end) as betAmount,
                        sum(case when liqType = 6 then lcoin else 0 end) as zjAmount,
                        0-sum(case when liqType = 107 then lfcoin else 0 end) as cashAmount,
                        sum(case when liqType = 1 then lcoin else 0 end) as rechargeAmount,
                        sum(case when liqType in (50,51,52,53) then coin else 0 end) as brokerageAmount
                    FROM
                        (
                            SELECT
                                a.uid,a.username,a.parentId, l.coin AS lcoin,l.fcoin AS lfcoin,l.liqType
                            FROM
                                gygy_members AS a
                            LEFT JOIN gygy_coin_log AS l ON a.uid = l.uid
                            WHERE a.sb = 0 $timeWhere/* AND l.uid NOT IN $tUidStr*/ AND 0 = (SELECT COUNT(1) FROM gygy_coin_log WHERE extfield0=l.extfield0 AND liqType IN (5,7)) limit 1
                        ) t
                    LEFT JOIN gygy_members AS t1 ON FIND_IN_SET(t.uid, t1.parents)
                    WHERE
                        t.parentId = 1
                    GROUP BY t.uid";
        }

		$Model = new \Think\Model();
		$list=$Model->query($sql);

		/*if(!$list) {
			//$uParentId2=$this->getValue("select parentId from {$this->prename}members where uid=?",$para['parentId']);
			//$user = M('members')->where(array('uid'=>$para['parentId']))->find();
			$list=array(array(
				'parentId'=>0,
				'uid'=>$para['parentId'],
				'username'=>'没有下级了'
				));
			$noChildren=true;
			//dump($noChildren);
		}*/
		//dump($list);

		/*$sql2="select sum(j.fanDianAmount) as fanDianAmount2, sum(j.betAmount) as betAmount2,sum(j.zjAmount) as zjAmount2,
		sum(j.cashAmount) as cashAmount2,sum(j.rechargeAmount) as rechargeAmount2,sum(j.brokerageAmount) as brokerageAmount2,sum(coin) as coin2 from
		(select u.username, u.coin, u.uid, u.parentId, hh.fanDianAmount,hh.betAmount, hh.zjAmount,hh.cashAmount,hh.rechargeAmount,hh.brokerageAmount  
		from gygy_members u ,
		(select l.uid,
		sum(case when l.liqType in (2,3) then l.coin else 0 end) as fanDianAmount,
		0-sum(case when l.liqType in (101,102,7) then l.coin else 0 end) as betAmount,
		sum(case when l.liqType=6 then l.coin else 0 end) as zjAmount,
		0-sum(case when l.liqType=107 then l.fcoin else 0 end) as cashAmount,
		sum(case when l.liqType=1 then l.coin else 0 end) as rechargeAmount,
		sum(case when l.liqType in (50,51,52,53) then l.coin else 0 end) as brokerageAmount 
		from gygy_coin_log l where 1 $timeWhere AND l.uid NOT IN $tUidStr group by l.uid) hh where hh.uid=u.uid $whereInt and 1 $userWhere2 group by u.uid) j";*/

        /*$sql2="select sum(j.fanDianAmount) as fanDianAmount2, sum(j.betAmount) as betAmount2,sum(j.zjAmount) as zjAmount2,
		sum(j.cashAmount) as cashAmount2,sum(j.rechargeAmount) as rechargeAmount2,sum(j.brokerageAmount) as brokerageAmount2,sum(coin) as coin2 from
		(select u.username, u.coin, u.uid, u.parentId, hh.fanDianAmount,hh.betAmount, hh.zjAmount,hh.cashAmount,hh.rechargeAmount,hh.brokerageAmount  
		from gygy_members u ,
		(select l.uid,
		sum(case when l.liqType = 2 then l.coin else 0 end) as fanDianAmount,
		0-sum(case when l.liqType = 101 then l.coin else 0 end) as betAmount,
		sum(case when l.liqType = 6 then l.coin else 0 end) as zjAmount,
		0-sum(case when l.liqType = 107 then l.fcoin else 0 end) as cashAmount,
		sum(case when l.liqType = 1 then l.coin else 0 end) as rechargeAmount,
		sum(case when l.liqType in (50,51,52,53) then l.coin else 0 end) as brokerageAmount 
		from gygy_coin_log l where 1  and 0 = (SELECT COUNT(1) FROM gygy_coin_log WHERE extfield0=l.extfield0 AND liqType IN (5,7)) $timeWhere group by l.uid limit 1) hh where hh.uid=u.uid $whereInt and 1 $userWhere2 group by u.uid) j";
        if (isset($para['user_type']) && $para['user_type'] == 1){
		    $sql2 = "SELECT
                        sum(coin) as coin2,
                        sum(case when liqType = 2 then lcoin else 0 end) as fanDianAmount2,
                        0-sum(case when liqType = 101 then lcoin else 0 end) as betAmount2,
                        sum(case when liqType = 6 then lcoin else 0 end) as zjAmount2,
                        0-sum(case when liqType = 107 then lfcoin else 0 end) as cashAmount2,
                        sum(case when liqType = 1 then lcoin else 0 end) as rechargeAmount2,
                        sum(case when liqType in (50,51,52,53) then coin else 0 end) as brokerageAmount2
                    FROM
                        (
                            SELECT
                                a.uid,a.username,a.parentId, l.coin AS lcoin,l.fcoin AS lfcoin,l.liqType
                            FROM
                                gygy_members AS a
                            LEFT JOIN gygy_coin_log AS l ON a.uid = l.uid
                            WHERE a.sb = 0 $timeWhere AND 0 = (SELECT COUNT(1) FROM gygy_coin_log WHERE extfield0=l.extfield0 AND liqType IN (5,7)) limit 1
                        ) t
                    LEFT JOIN gygy_members AS t1 ON FIND_IN_SET(t.uid, t1.parents)
                    WHERE
                        t.parentId = 1";
        }
		$all=$Model->query($sql2);
		$all = $all[0];
		$this->assign('all',$all);*/

		$this->recordList($list);
		$this->assign('para',$para);

//		$this->assign('noChildren',$noChildren);

		$this->meta_title = '综合统计';
		$this->display();
	}

    public function date_i_type()
    {

		if(I('post.searchTime')){
			$fromTime=strtotime(I('post.searchTime'));
			$toTime=strtotime(I('post.searchTime'))+24*3600-1;
		}else{
            $fromTime=strtotime(date('Y-m-d 00:00:00'));
            $toTime=$fromTime+24*3600-1;
        }
        dump($fromTime);dump($toTime);
        $map['actionTime'] = array('between',array($fromTime,$toTime));
        if (I('isTest') == 2) {
            $map['istest'] = ['EQ', 0];
        } elseif (I('isTest') == 3) {
            $map['istest'] = ['EQ', 1];
        }
        $map1 = $map;
        $map1['a.isDelete'] = ['EQ', 0];
		$rs = M('bets a')->join('gygy_type AS b ON a.type=b.id','LEFT')
            ->where($map1)
            ->field('a.type,0 fd,sum(a.bets_money) tz,sum(a.bonus) fj,b.shortName cz,sum(a.bets_money) - sum(a.fanDianAmount) - sum(a.bonus) yk')
            ->group('a.type')
            ->select();

        $map['liqType'] = 2;
		$fanDianAmount = M('coin_log')->where($map)->field('sum(coin) as fd,type')->group('type')->select();
		foreach ($rs as $k => $v){
            foreach ($fanDianAmount as $value){
                if ($v['type'] == $value['type']){
                    $rs[$k]['fd'] = $value['fd'];
                }
            }
        }

        $this->assign('rs',$rs);
        $this->assign('isTest',I('isTest'));
		$this->display();

    }

	public function date_type_i_no(){
		$type=I('type','1');
		$this->assign('type',$type);

		//types
		$types = M('type')->where(array('enable'=>1))->select();
		$this->assign('types',$types);

		//date
		$date=I('date',date('Y-m-d'));
		$fromTime=strtotime($date);
		$toTime=$fromTime+24*3600-1;
		$this->assign('date',$date);

		/*$sql="
		select d.*,tz-fd-fj yk
		from(
			select actionNo qs, sum(fanDianAmount) fd, sum(actionNum*mode*beishu) tz, sum(bonus) fj 
			from gygy_bets
			where (type=$type and actionTime between $fromTime and $toTime AND istest=0 AND isDelete=0)
			group by actionNo
		) d
		order by d.qs desc
		";
		$rs=M()->query($sql);
		$this->recordList($rs);*/

        $pageIndex = I('p') > 0 ? I('p') : 1;
        if( isset($request['r']) ){
            $listRows = (int)I('r');
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }

        $Model = new \Think\Model();
        $map['type'] = $type;
        $map['istest'] = 0;
        $map['isDelete'] = 0;
        $map['actionTime'] = ['between',[$fromTime,$toTime]];
        $field = 'actionNo qs,sum(fanDianAmount) fd,sum(actionNum * MODE * beishu) tz,sum(bonus) fj,sum(actionNum * MODE * beishu) - sum(fanDianAmount) - sum(bonus) yk';
        $lists = $Model->table('__BETS__')->where($map)->field($field)->group('actionNo')->order('actionNo DESC')->page($pageIndex,$listRows)->select();

        $request    =   (array)I('request.');
        $total      =   $Model->table('__BETS__')->where($map)->group('actionNo')->count();
        $total      =   $total > 0 ? $total : 0;
        $page       =   new \COM\Page($total, $listRows, $request);
        $p			=	$page->show();
        $this->assign('_list', $lists);
        if ($total > $listRows){
            $this->assign('_page', $p? $p: '');
        }

		$this->display();
	}

	// 获取单笔投注排行列表
	public function betRanking(){
        $betList = M('bets')->field('*,beiShu*mode*actionNum*(fpEnable+1) as betAmount')->order('betAmount desc')->limit(60)->select();
        $this->recordList($betList);

        $this->getTypes();
        $this->assign('types',$this->types);

        $this->getPlayeds();
        $this->assign('playeds',$this->playeds);

        $this->meta_title = '投注排行';
        $this->display();
    }
}

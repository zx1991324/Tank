<?php
namespace hackathon\php;

error_reporting(E_ALL);
var_dump('server is runing.....................');
require_once '/thrift/lib/php/lib/Thrift/ClassLoader/ThriftClassLoader.php';
require_once '/hackathon/hackathon/gen_php/tank/player/PlayerServer.php';
require_once '/hackathon/hackathon/gen_php/tank/player/Types.php';
require_once '/hackathon/hackathon/gen_php/tank/player/AStarSearch.php';
use tank\player\Node;
use tank\player\Order;
use tank\player\Position;
use tank\player\Tank;
use tank\player\Shell;
use tank\player\GameState;
use tank\player\Args;
use Thrift\ClassLoader\ThriftClassLoader;
use tank\player\PlayerServerIf;
use tank\player\PlayerServerProcessor;
use tank\player\AStar;
use tank\player\myNode;


$GEN_DIR = realpath(dirname(__FILE__) . '/../') . '/gen_php';
$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', '/thrift/lib/php/lib/');
$loader->registerDefinition('hackathon', $GEN_DIR);
$loader->register();

if (php_sapi_name() == 'cli') {
    ini_set('display_errors', "stderr");
}

use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TPhpStream;
use Thrift\Transport\TBufferedTransport;

class PlayerServerHandler implements PlayerServerIf
{

    private $arrArgument = null;
    private $intTankNum = 0;
    private $arrTanks = null;
    private $arrState = null;
    private $flagPos = null;
    private $AStarMap = null;
    private $arrTankStates = null;
    private $arrTankStatesCopy = null;
    private $arrEnemyTankStates = null;
    private $myFirstTank = array();

    //子弹状态
    private $arrShellStates = null;

    //以下为第一张地图和第二张地图设置固定站点
    private $arrOneMap;
    private $arrTwoMap;
    private $arrTargetPosition;
    private $arrTank2Pos;
    private $MapId;

    private $ARRAY_ORDER = array(
        0 => 'stop',
        1 => 'turnTo',
        2 => 'fire',
        3 => 'move',
    );
    private $ARRAY_DIRECTION = array(
        1 => 'UP',
        2 => 'DOWN',
        3 => 'LEFT',
        4 => 'RIGHT',
    );


    public function __construct()
    {
        //初始化函数，载入地图
        //第一张地图
        $this->arrOneMap = array();
        $this->arrOneMap[] = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        $this->arrOneMap[] = array(1, 2, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 2, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 1, 1, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 2, 2, 1, 2, 2, 2, 2, 2, 2, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 2, 2, 1, 2, 2, 2, 2, 2, 2, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 2, 2, 2, 2, 2, 2, 2, 2, 2, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 2, 2, 2, 2, 2, 2, 2, 2, 2, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 2, 2, 2, 2, 2, 2, 2, 2, 2, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 2, 2, 2, 2, 2, 2, 1, 2, 2, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 2, 2, 2, 2, 2, 2, 1, 2, 2, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 1, 1, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 2, 1);
        $this->arrOneMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 2, 2, 1);
        $this->arrOneMap[] = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        //第一张图坦克的站点
        //左上角出生位
        $this->arrTargetPosition[0] = array(
            array('x' => 6, 'y' => 9),
            array('x' => 6, 'y' => 10),
            array('x' => 6, 'y' => 5),
            array('x' => 6, 'y' => 6),
        );
        //右下角出生位
        $this->arrTargetPosition[1] = array(
            array('x' => 12, 'y' => 8),
            array('x' => 12, 'y' => 9),
            array('x' => 12, 'y' => 13),
            array('x' => 12, 'y' => 12),
        );

        //第二张地图
        $this->arrTwoMap = array();
        $this->arrTwoMap[] = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->arrTwoMap[] = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        //第二张地图坦克站点
        //左上角出生位
        $this->arrTargetPosition[2] = array(
            array('x' => 14, 'y' => 16),
            array('x' => 13, 'y' => 15),
            array('x' => 24, 'y' => 13),
            array('x' => 18, 'y' => 15),
        );
        //右下角出生位
        $this->arrTargetPosition[3] = array(
            array('x' => 15, 'y' => 13),
            array('x' => 10, 'y' => 16),
            array('x' => 16, 'y' => 14),
            array('x' => 12, 'y' => 15),
        );

    }

    //地图比对函数
    private function compareMap($MyMap)
    {
        if (empty($this->AStarMap) || empty($MyMap) || !isset($this->AStarMap[0]) || !isset($MyMap[0])) {
            //地图不存在或者不是二位数组
            echo "compare map  failed !\n";
            return false;
        }
        //匹配地图尺寸
        if (count($this->AStarMap) != count($MyMap)) {
            //X轴不一致
            return false;
        }
        if (count($this->AStarMap[0]) != count($MyMap[0])) {
            //Y轴不一致
            return false;
        }

        //比较地图内容
        for ($i = 0; $i < count($MyMap); $i++) {
            for ($j = 0; $j < count($MyMap[0]); $j++) {
                if ($MyMap[$i][$j] != $this->AStarMap[$i][$j]) {
                    //地图出现不一致
                    return false;
                }
            }
        }
        return true;
    }

    //载入地图函数
    //好像游戏引擎先加载地图后加载游戏参数
    public function uploadMap(array $gamemap)
    {
        $this->AStarMap = $gamemap;
        //判断地图，如果是第一张或者第二张地图，直接占位
        if ($this->compareMap($this->arrOneMap)) {
            //命中第一张地图,只留下第一张地图的占位信息
            //echo "Map One OK !\n";
            $this->MapId = 1;
            unset($this->arrTargetPosition[2]);
            unset($this->arrTargetPosition[3]);
        } else {
            if ($this->compareMap($this->arrTwoMap)) {
                //命中第二张地图,只留下第二张地图的占位信息
                //echo "Map Two OK !\n";

                $this->MapId = 2;
                $this->arrTargetPosition[0] = $this->arrTargetPosition[2];
                $this->arrTargetPosition[1] = $this->arrTargetPosition[3];
                unset($this->arrTargetPosition[2]);
                unset($this->arrTargetPosition[3]);

            } else {
                //命中后三张地图，默认处理
                //echo "defalut Map OK !\n";
                //后续加上
                unset($this->arrTargetPosition);
                $this->getDefaultPos();
            }
        }
    }

    private function getDefaultPos()
    {
        $CenterPosX = floor(count($this->AStarMap) / 2);
        $CenterPosY = floor(count($this->AStarMap[0]) / 2);
        $intNum = 0;
        $boolRes = false;
        for ($i = $CenterPosX - 4; $i < $CenterPosX + 4; $i++) {
            if($boolRes) {
                break;
            }
            for ($j = $CenterPosY - 4; $j < $CenterPosY + 4; $j++) {
                if (1 != $this->AStarMap [$i][$j]) {
                    if (abs($j - $CenterPosY) <= 1 || abs($i - $CenterPosX) <= 1) {
                        //只要外面的大圈坐标，不要内圈的
                        continue;
                    }

                    $this->arrTargetPosition[0][] = array('x' => $i, 'y' => $j);
                    $intNum++;
                }

                if ($this->intTankNum == $intNum) {
                    $this->arrTargetPosition[1] = $this->arrTargetPosition[0];
                    $boolRes = true;
                    break;
                }
            }
        }

        if ($intNum < $this->intTankNum) {
            for ($i = $intNum; $i < $this->intTankNum; $i++) {
                $this->arrTargetPosition[0][$i] = array('x' => $CenterPosX, 'y' => $CenterPosY);
                $this->arrTargetPosition[1] = $this->arrTargetPosition[0];
            }
        }
    }


    /**
     * @param rgs $arguments
     */
    public function uploadParamters(Args $arguments)
    {
        //var_dump($arguments);
        $this->arrArgument = json_decode(json_encode($arguments), true);
    }

    /**
     * Assign a list of tank id to the player.
     * each player may have more than one tank, so the parameter is a list.
     *
     *
     * @param int[] $tanks
     */
    public function assignTanks(array $tanks)
    {
        $this->arrTanks = $tanks;
        $this->intTankNum = count($tanks);
    }

    /**
     * Report latest game state to player.
     *
     *
     * @param \tank\player\GameState $state
     */
    public function latestState(GameState $state)
    {
        $arrState = json_decode(json_encode($state), true);
        //echo json_encode($arrState) . "&&&&&&&&&&&&\n";
        $this->arrTankStates = array();
        $this->arrEnemyTankStates = array();
        foreach ($arrState['tanks'] as $arrTank) {
            //活着的坦克
            if (!empty($arrTank['hp'])) {
                if (in_array($arrTank['id'], $this->arrTanks)) {
                    //己方坦克
                    $this->arrTankStates[$arrTank['id']] = $arrTank;
                } else {
                    //敌方坦克
                    $this->arrEnemyTankStates[] = $arrTank;
                }
            }
        }
        if (isset($arrState['flagPos'])) {
            $this->flagPos = $arrState['flagPos'];
        } else {
            $this->flagPos = array();
        }

        //为己方每个tank设定移动的目标地址
        if (empty($this->arrTank2Pos)) {
            //判断出生位置 0：左上出生位，1：右下出生位
            $intBirthPlace = 0;
            foreach ($this->arrTanks as $tankId) {
                if (isset($this->arrTankStates[$tankId])) {
                    $fristTank = $this->arrTankStates[$tankId];
                    if ($fristTank['pos']['x'] < 5 || $fristTank['pos']['y'] < 5) {
                        //出生在左上角
                        $intBirthPlace = 0;
                    } else {
                        $intBirthPlace = 1;
                    }
                }
            }

            //按照顺序，初始化目标地址
            foreach ($this->arrTanks as $key => $tankId) {
                $this->arrTank2Pos[$tankId] = $this->arrTargetPosition[$intBirthPlace][$key];
            }
        }

        //记下子弹的状态
        if (isset($arrState['shells']) && !empty($arrState['shells'])) {
            $this->arrShellStates = $arrState['shells'];
        } else {
            $this->arrShellStates = array();
        }

        //留个备份
        $this->arrTankStatesCopy = $this->arrTankStates;
    }

    //选一个tank去抢旗子
    public function getCandidate()
    {
        foreach ($this->arrTankStates as $TankId => $arrTankStates) {
            if ($arrTankStates['pos']['y'] == $this->flagPos['y'] || $arrTankStates['pos']['x'] == $this->flagPos['x']) {
                return $arrTankStates;
            }
        }

        //这里的话可能有坦克已经阵亡了
        //在选一个离旗子最近的tank去抢
        $MinDistance = PHP_INT_MAX;
        $CandidateId = -1;
        foreach ($this->arrTankStates as $TankId => $arrTankStates) {
            $TmpDistance = abs($arrTankStates['pos']['y'] - $this->flagPos['y']) + abs($arrTankStates['pos']['x'] - $this->flagPos['x']);
            if ($TmpDistance < $MinDistance) {
                $CandidateId = $TankId;
            }
        }

        if (-1 !== $CandidateId) {
            return $this->arrTankStates[$CandidateId];
        }
        return false;
    }

    /**
     * Ask for the tank orders for this round.
     * If this funtion does not return orders within the given round timeout, game engine will make all this player's tank to stick around.
     *
     * @return \tank\player\Order[]
     */
    public function getNewOrders() {
        $arrOrders = array();
        $arrOrderList = array();

        //开火具有最高优先级
        foreach ($this->arrTankStates as $TankId => $arrTankStates) {
            $arrOrderTmp = array();
            $intDir = $this->fire($arrTankStates);
            if (0 != $intDir) {
                $arrOrderTmp['tankId'] = $TankId;
                $arrOrderTmp['order'] = 'fire';
                $arrOrderTmp['dir'] = $intDir;
                //echo "fire[1]  " . json_encode($arrOrderTmp) . "\n";
                $objOrder = new Order($arrOrderTmp);
                $arrOrderList[] = $objOrder;
                //该坦克会直接开火，不在接受新的指令
                unset($this->arrTankStates[$TankId]);
            }
        }

        //第二优先级规避子弹

        if (!empty($this->flagPos)) {
            //出现旗子了，派一个坦克去抢
            $objEndNode = new myNode($this->flagPos['x'], $this->flagPos['y']);
            $arrTankStates = $this->getCandidate();

            //echo  json_encode($arrTankStates) ."CCCCCCCCCCCCC\n";
            if (!empty($arrTankStates)) {
                //开始寻路
                $TankId = $arrTankStates['id'];
                $arrOrderTmp = array();
                $objStartNode = new myNode($arrTankStates['pos']['x'], $arrTankStates['pos']['y']);
                $ObjAStar = new AStar($this->AStarMap);
                $arrNextNode = $ObjAStar->aStarSearch($objStartNode, $objEndNode);
                $intDir = $this->getDirection($arrNextNode, $objStartNode);
                if ($intDir == $arrTankStates['dir']) {
                    //不需要转向
                    //这里需要判断一下下一步会不会冲突（两个坦克同时进入一个格子会都动不了）
                    $boolRes = $this->checkConflict($arrOrderList, $arrNextNode);
                    if (!$boolRes) {
                        $arrOrderTmp['order'] = 'move';
                        $arrOrderTmp['tankId'] = $TankId;
                    }
                } else {
                    //需要先转向
                    $arrOrderTmp['order'] = 'turnTo';
                    $arrOrderTmp['tankId'] = $TankId;
                }

                //如果下一步要移动，需要检测一下是否安全
                if('move' == $arrOrderTmp['order']) {
                    if($this->CheckDangerious($arrNextNode)) {
                        //走下一步有风险
                       // echo "[1]下一步有风险\n";
                        unset($arrOrderTmp);
                    }
                }


                if (!empty($arrOrderTmp)) {
                    $arrOrderTmp['dir'] = $intDir;
                    $objOrder = new Order($arrOrderTmp);
                    $arrOrderList[] = $objOrder;
                }
                unset($this->arrTankStates[$TankId]);
            }
        }

        //没有旗子，去目标位置蹲守
        //echo "没有旗子，去目标位置蹲守\n";
        foreach ($this->arrTankStates as $TankId => $arrTankStates) {
            $arrOrderTmp = array();
            $objStartNode = new myNode($arrTankStates['pos']['x'], $arrTankStates['pos']['y']);
            $ObjAStar = new AStar($this->AStarMap);
            //旗子未出现，先去初始占位
            $objEndNode = new myNode($this->arrTank2Pos[$TankId]['x'], $this->arrTank2Pos[$TankId]['y']);
            //echo 'start:' . $arrTankStates['pos']['x'] . ',' . $arrTankStates['pos']['y'] . "\n";
            //echo 'end:' . $this->arrTank2Pos[$TankId]['x'] . ',' . $this->arrTank2Pos[$TankId]['y'] . "\n";
            if ($objStartNode->getIntX() == $objEndNode->getIntX() && $objStartNode->getIntY() == $objEndNode->getIntY()) {
                //已经到了终点
                //左右开火
                if($objStartNode->getIntX() == count($this->AStarMap) / 2) {
                    if($objStartNode->getIntY() < count($this->AStarMap[0]) / 2) {
                        $arrOrderTmp['dir'] = 4;
                    }else{
                        $arrOrderTmp['dir'] = 3;
                    }
                }
                //上下开火
                if ($objStartNode->getIntX() < count($this->AStarMap) / 2) {
                    $arrOrderTmp['dir'] = 2;
                } else {
                    $arrOrderTmp['dir'] = 1;
                }
                //判断是否有自己人
                $Ret = $this->getFristWall($arrTankStates['pos'], $arrOrderTmp['dir']);
                if (!$this->CheckWall($arrTankStates['pos'], $Ret)) {
                    continue;
                } else {
                    $arrOrderTmp['tankId'] = $TankId;
                    $arrOrderTmp['order'] = 'fire';
                    //echo "fire[2]  " . json_encode($arrOrderTmp) . "\n";
                    $objOrder = new Order($arrOrderTmp);
                    $arrOrderList[] = $objOrder;
                    continue;
                }
            }

            $arrNextNode = $ObjAStar->aStarSearch($objStartNode, $objEndNode);
            //echo "next:" . $arrNextNode->getIntX() . ',' . $arrNextNode->getIntY() . "-----------------\n";
            $intDir = $this->getDirection($arrNextNode, $objStartNode);
            if ($intDir == $arrTankStates['dir']) {
                //不需要转向
                //这里需要判断一下下一步会不会冲突（两个坦克同时进入一个格子会都动不了）
                $boolRes = $this->checkConflict($arrOrderList, $arrNextNode);
                if (!$boolRes) {
                    $arrOrderTmp['order'] = 'move';
                    $arrOrderTmp['tankId'] = $TankId;
                }
            } else {
                //需要先转向
                $arrOrderTmp['order'] = 'turnTo';
                $arrOrderTmp['tankId'] = $TankId;
            }

            //如果下一步要移动，需要检测一下是否安全
            if('move' == $arrOrderTmp['order']) {
                if($this->CheckDangerious($arrNextNode)) {
                    //走下一步有风险
                   // echo "[2]下一步有风险\n";
                  //  echo json_encode($this->arrShellStates) . "BBBBBBBBB";
                   // echo $arrNextNode->getIntX().','.$arrNextNode->getIntY() . "BBBBBBBBB";
                    unset($arrOrderTmp);
                }
            }
            //else{
            //    echo "[2]无风险，OK\n";
            //}

            if (!empty($arrOrderTmp)) {
                $arrOrderTmp['dir'] = $intDir;
            //    echo json_encode($arrOrderTmp) . "\n";
                $objOrder = new Order($arrOrderTmp);
                $arrOrderList[] = $objOrder;
            }
        }
        return $arrOrderList;
    }


    //检测下一步是否安全
    private function  CheckDangerious(myNode $arrNextNode){
        //判断子弹的飞行路线
        foreach($this->arrShellStates as $Shell ) {
            //下一步与子弹同列
            if($Shell['pos']['y'] == $arrNextNode->getIntY() ) {
                if(1 == $Shell['dir'] && $Shell['pos']['x'] >= $arrNextNode->getIntX()) {
                    //子弹向上飞行
                    if($this->CheckWall($Shell['pos'] , array('x'=>$arrNextNode->getIntX(),'y'=>$arrNextNode->getIntY()))){
                       return true;
                    }
                }

                if(2 == $Shell['dir'] && $Shell['pos']['x'] <= $arrNextNode->getIntX()) {
                    //子弹向下飞行
                    if($this->CheckWall($Shell['pos'] , array('x'=>$arrNextNode->getIntX(),'y'=>$arrNextNode->getIntY()))){
                        return true;
                    }
                }
            }

            //下一步与子弹同排
            if($Shell['pos']['x'] == $arrNextNode->getIntX() ) {
                if(3 == $Shell['dir'] && $Shell['pos']['y'] >= $arrNextNode->getIntY()) {
                    //子弹向左飞行
                    if($this->CheckWall($Shell['pos'] , array('x'=>$arrNextNode->getIntX(),'y'=>$arrNextNode->getIntY()))){
                        return true;
                    }
                }

                if(4 == $Shell['dir'] && $Shell['pos']['y'] <= $arrNextNode->getIntY()) {
                    //子弹向下飞行
                    if($this->CheckWall($Shell['pos'] , array('x'=>$arrNextNode->getIntX(),'y'=>$arrNextNode->getIntY()))){
                        return true;
                    }
                }
            }
        }

        return false;
    }

    //根据当前位置和下一步位置，给出下一步的方向
    private function getDirection(myNode $arrNextNode, myNode $objStartNode)
    {
        $intDir = '';
        if ($arrNextNode->getIntX() == $objStartNode->getIntX()) {
            if ($arrNextNode->getIntY() < $objStartNode->getIntY()) {
                $intDir = 3;
            } else {
                $intDir = 4;
            }
        } else {
            if ($arrNextNode->getIntX() < $objStartNode->getIntX()) {
                $intDir = 1;
            } else {
                $intDir = 2;
            }
        }
        return $intDir;
    }

    //判断步入一个格子冲突
    //这里就先不判断坦克目前的位置阻挡的情况，逻辑有点小复杂，而且这个case可以在寻路算法中处理。
    private function checkConflict($arrOrderList, $arrNextNode)
    {
        foreach ($arrOrderList as $arrOrder) {
            if ('move' == $arrOrder->getOrder()) {
                //计算该坦克的下一步落点
                //这个地方可以优化，先不优化了
                //$tankId = $arrOrder->getTankId();
                //$tankStates = $this->arrTankStates[$tankId];

                foreach ($this->arrTankStatesCopy as $arrTankStates) {
                    if ($arrOrder->getTankId() == $arrTankStates['id']) {
                        $NodeNextStep = '';
                        switch ($arrOrder->getDirection()) {
                            case 1:
                                //up 上
                                $NodeNextStep = new myNode($arrTankStates['pos']['x'] - 1, $arrTankStates['pos']['y']);
                                break;
                            case 2:
                                //down  下
                                $NodeNextStep = new myNode($arrTankStates['pos']['x'] + 1, $arrTankStates['pos']['y']);
                                break;
                            case 3:
                                //left 左
                                $NodeNextStep = new myNode($arrTankStates['pos']['x'], $arrTankStates['pos']['y'] - 1);
                                break;
                            case 4:
                                //right  右
                                $NodeNextStep = new myNode($arrTankStates['pos']['x'], $arrTankStates['pos']['y'] + 1);
                                break;
                            default:
                                //exception 异常
                                $NodeNextStep = false;
                        }
                        //echo $NodeNextStep->getIntX() . "-:-" .$NodeNextStep->getIntY() . "\n";
                        if (!empty($NodeNextStep) && $NodeNextStep->getIntX() == $arrNextNode->getIntX() && $NodeNextStep->getIntY() == $arrNextNode->getIntY()) {
                            //命中：即将发送的命令中会出现两个坦克走入一个格子的情况
                            //即将发生冲突
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $arrMyState //我方坦克状态数组
     * @return  bool         //0不开枪 1向上开枪 2向下开枪
     */
    public function fire($arrMyState)
    {
        $arrEnemyTankStates = $this->arrEnemyTankStates;
        foreach ($arrEnemyTankStates as $arrEnemyTankState) {
            if ($arrMyState['hp'] <= 0 || $arrEnemyTankState['hp'] <= 0 || empty($arrMyState) || empty($arrEnemyTankState)) {
                return 0;
            }
            $arrMyPos = $arrMyState['pos'];
            $arrElmPos = $arrEnemyTankState['pos'];

            if ($arrElmPos['y'] == $arrMyPos['y'] && abs($arrElmPos['x'] - $arrMyPos['x']) <= 2 * $this->arrArgument['shellSpeed']) {
                //向上开火
                if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                    continue;
                }
                if ($arrElmPos['x'] < $arrMyPos['x']) {
                    return 1;
                } else {
                    //向下开火
                    return 2;
                }
            }

            if ($arrElmPos['x'] == $arrMyPos['x'] && abs($arrElmPos['y'] - $arrMyPos['y']) <= 2 * $this->arrArgument['shellSpeed']) {
                if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                    continue;
                }
                if ($arrElmPos['y'] < $arrMyPos['y']) {
                    //向左开火
                    return 3;
                } else {
                    //向右开火
                    return 4;
                }
            }

            //预瞄开火
            if (abs($arrMyPos['y'] - $arrElmPos['y']) == $this->arrArgument['tankSpeed'] && abs($arrMyPos['x'] - $arrElmPos['x']) == $this->arrArgument['shellSpeed']) {
                if ($arrMyPos['y'] > $arrElmPos['y'] && $arrMyPos['x'] > $arrElmPos['x'] && 4 == $arrEnemyTankState['dir']) {
                    if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                        continue;
                    }
                    return 1;
                }
                if ($arrMyPos['y'] < $arrElmPos['y'] && $arrMyPos['x'] > $arrElmPos['x'] && 3 == $arrEnemyTankState['dir']) {
                    if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                        continue;
                    }
                    return 1;
                }
                if ($arrMyPos['y'] > $arrElmPos['y'] && $arrMyPos['x'] < $arrElmPos['x'] && 4 == $arrEnemyTankState['dir']) {
                    if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                        continue;
                    }
                    return 2;
                }
                if ($arrMyPos['y'] < $arrElmPos['y'] && $arrMyPos['x'] < $arrElmPos['x'] && 3 == $arrEnemyTankState['dir']) {
                    if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                        continue;
                    }
                    return 2;
                }
            }

            if (abs($arrMyPos['x'] - $arrElmPos['x']) == $this->arrArgument['tankSpeed'] && abs($arrMyPos['y'] - $arrElmPos['y']) == $this->arrArgument['shellSpeed']) {
                if ($arrMyPos['x'] > $arrElmPos['x'] && $arrMyPos['y'] > $arrElmPos['y'] && 2 == $arrEnemyTankState['dir']) {
                    if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                        continue;
                    }
                    return 3;
                }
                if ($arrMyPos['x'] < $arrElmPos['x'] && $arrMyPos['y'] > $arrElmPos['y'] && 1 == $arrEnemyTankState['dir']) {
                    if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                        continue;
                    }
                    return 3;
                }
                if ($arrMyPos['x'] > $arrElmPos['x'] && $arrMyPos['y'] < $arrElmPos['y'] && 2 == $arrEnemyTankState['dir']) {
                    if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                        continue;
                    }
                    return 4;
                }
                if ($arrMyPos['x'] < $arrElmPos['x'] && $arrMyPos['y'] < $arrElmPos['y'] && 1 == $arrEnemyTankState['dir']) {
                    if (!$this->CheckWall($arrMyPos, $arrElmPos)) {
                        continue;
                    }
                    return 4;
                }
            }
        }
        return 0;
    }


    private function CheckWall($arrMyPos, $arrElmPos = array())
    {

        if ($arrElmPos['y'] == $arrMyPos['y']) {
            if ($arrElmPos['x'] < $arrMyPos['x']) {
                $indexStart = $arrElmPos['x'];
                $indexEnd = $arrMyPos['x'];
            } else {
                $indexStart = $arrMyPos['x'];
                $indexEnd = $arrElmPos['x'];
            }

            foreach ($this->arrTankStatesCopy as $arrMyTank) {
                if ($arrMyTank['pos']['y'] == $arrElmPos['y'] && $arrMyTank['pos']['x'] > $indexStart && $arrMyTank['pos']['x'] < $indexEnd) {
                    return false;
                }
            }

            for ($i = $indexStart; $i < $indexEnd; $i++) {
                if (1 == $this->AStarMap[$i][$arrElmPos['y']]) {
                    return false;
                }
            }
        }

        if ($arrElmPos['x'] == $arrMyPos['x']) {
            if ($arrElmPos['y'] < $arrMyPos['y']) {
                $indexStart = $arrElmPos['y'];
                $indexEnd = $arrMyPos['y'];
            } else {
                $indexStart = $arrMyPos['y'];
                $indexEnd = $arrElmPos['y'];
            }

            foreach ($this->arrTankStatesCopy as $arrMyTank) {
                if ($arrMyTank['pos']['x'] == $arrElmPos['x'] && $arrMyTank['pos']['y'] > $indexStart && $arrMyTank['pos']['y'] < $indexEnd) {
                    return false;
                }
            }

            for ($i = $indexStart; $i < $indexEnd; $i++) {
                if (1 == $this->AStarMap[$arrElmPos['x']][$i]) {
                    return false;
                }
            }
        }
        return true;
    }


    private function getFristWall($arrMyPos, $shellDir)
    {
        $resPos = array();
        if (2 == $shellDir || 1 == $shellDir) {
            foreach ($this->AStarMap as $key => $Map) {
                if ($key <= $arrMyPos['x'] && 2 == $shellDir) {
                    //还没有到射击弹道
                    continue;
                }
                if (2 == $shellDir && 1 == $Map[$arrMyPos['y']]) {
                    $resPos['x'] = $key;
                    $resPos['y'] = $arrMyPos['y'];
                    break;
                }

                if (1 == $shellDir) {
                    if ($key >= $arrMyPos['x']) {
                        break;
                    }
                    if (1 == $Map[$arrMyPos['y']]) {
                        //排除墙的干扰
                        $resPos['x'] = $key + 1;
                        $resPos['y'] = $arrMyPos['y'];
                    }
                }

            }
        }

        if (3 == $shellDir || 4 == $shellDir) {
            $Map = $this->AStarMap[$arrMyPos['x']];
            for ($i = $arrMyPos['y']; $i >= 0 && $i <= count($Map);) {
                if (1 == $Map[$i]) {
                    $resPos['x'] = $arrMyPos['x'];
                    $resPos['y'] = $i;
                    break;
                }
                if (3 == $shellDir) {
                    $i--;
                } else if (4 == $shellDir) {
                    $i++;
                }
            }
            if (3 == $shellDir) {
                //排除墙的干扰
                $resPos['y'] + 1;
            }
        }
        return $resPos;
    }
}

header('Content-Type', 'application/x-thrift');
if (php_sapi_name() == 'cli') {
    echo PHP_EOL;
}
use Thrift\Factory\TBinaryProtocolFactory;
use Thrift\Factory\TTransportFactory;
use Thrift\Server\TForkingServer;
use Thrift\Server\TServerSocket;

$serverTransport = new TServerSocket("0.0.0.0", 80);
$clientTransport = new TTransportFactory();
$binaryProtocol = new TBinaryProtocolFactory();

$handler = new PlayerServerHandler();
$processor = new PlayerServerProcessor($handler);

$server = new TForkingServer(
    $processor,
    $serverTransport,
    $clientTransport,
    $clientTransport,
    $binaryProtocol,
    $binaryProtocol
);
$server->serve();
/*$transport = new TBufferedTransport(new TPhpStream(TPhpStream::MODE_R | TPhpStream::MODE_W));
$protocol = new TBinaryProtocol($transport,true,true);

$transport->open();
$processor->process($protocol,$protocol);
$transport->close();*/

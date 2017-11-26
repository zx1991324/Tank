<?php
namespace tank\player;
/**
 * Created by PhpStorm.
 * User: zhanghao03@iwaimai.baidu.com
 * Date: 2017/11/23
 * Time: 11:07
 */
/*$arrMap[] = array(1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);
$arrMap[] = array(1,2,2,2,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1);
$arrMap[] = array(1,2,0,1,0,0,0,0,0,1,0,0,0,0,0,0,0,0,1);
$arrMap[] = array(1,1,1,1,0,0,0,0,0,1,0,0,0,0,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,1);
$arrMap[] = array(1,0,0,1,1,1,1,1,1,1,1,1,1,1,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,2,2,1,2,2,2,2,2,2,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,2,2,1,2,2,2,2,2,2,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,2,2,2,2,2,2,2,2,2,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,2,2,2,2,2,2,2,2,2,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,2,2,2,2,2,2,2,2,2,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,2,2,2,2,2,2,1,2,2,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,2,2,2,2,2,2,1,2,2,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,1,1,1,1,1,1,1,1,1,1,1,0,0,1);
$arrMap[] = array(1,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,1);
$arrMap[] = array(1,0,0,0,0,0,0,0,0,1,0,0,0,0,0,1,1,1,1);
$arrMap[] = array(1,0,0,0,0,0,0,0,0,1,0,0,0,0,0,1,0,2,1);
$arrMap[] = array(1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,2,2,2,1);
$arrMap[] = array(1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);

$NodeStart = new myNode(1,1);
$NodeEnd = new myNode(8,8);

$objAstar = new AStar($arrMap);
$res = $objAstar->aStarSearch($NodeStart,$NodeEnd);
var_dump($res);*/

class AStar
{
    private $_openList;
    private $_closeList;
    private $_arrMap;
    private $_arrParentMap;

    public function  __construct($arrMap) {
        $this->_openList = array();
        $this->_closeList = array();
        $this->_arrMap = $arrMap;
        $this->_arrParentMap = array();
        $this->initCloseList();
    }

    //把地图障碍物添加到CloseList中
    //初始化操作
    private  function  initCloseList() {
        for ($i = 0 ; $i < count($this->_arrMap) ; $i++) {
            for($j = 0 ; $j < count($this->_arrMap[$i]); $j++) {
                if(1 == $this->_arrMap[$i][$j]) {
                    //障碍物
                    $this->_closeList[] = new myNode($i ,$j);
                }
            }
        }
    }

    //检验Node（x,y）是否在CloseList中
    function  checkIsInCloseList($NodeCheck){
        if(empty($this->_closeList)) {
            return  false;
        }

        foreach($this->_closeList as $oneCloseNode) {
            if($oneCloseNode->getIntX() == $NodeCheck->getIntX() && $oneCloseNode->getIntY() == $NodeCheck->getIntY()) {
                return true;
            }
        }

        return  false;
    }

    public function aStarSearch($NodeStart, $NodeEnd) {
        if($NodeStart->getIntX() == $NodeEnd->getIntX() && $NodeStart->getIntY() == $NodeEnd->getIntY()) {
            //起点等于终点
            //echo "miao , miao ,miao~  到终点了\n";
            return $NodeEnd;
        }
        // 把起点加入 open list
        $this->_openList[] = $NodeStart;
        //主循环，每一轮检查一个当前方格节点
        while (count($this->_openList) > 0) {
            // 在OpenList中查找 F值最小的节点作为当前方格节点
            $NodeCurrent = $this->findMinNode();
            //echo $NodeCurrent->getIntX() . "--,--" .$NodeCurrent->getIntY() ."\n";
            // 找到所有邻近节点
            $arrNodeNeighbors = $this->findNeighbors($NodeCurrent);
            foreach ($arrNodeNeighbors  as  $neighbor) {
                if (!$this->findInOpenList($neighbor)) {
                    //邻近节点不在OpenList中，标记父亲、G、H、F，并放入OpenList
                    $neighbor->setIntParentX($NodeCurrent->getIntX());
                    $neighbor->setIntParentY($NodeCurrent->getIntY());

                    $this->_arrParentMap[$neighbor->getIntX()][$neighbor->getIntY()] = $NodeCurrent;
                    //计算从起点到目前点走了多少步
                    $neighbor->setIntG($NodeCurrent->getIntG()+1);
                    //计算到终点的曼哈顿距离
                    $neighbor->setIntH(abs($NodeEnd->getIntX()-$neighbor->getIntX()) + abs($NodeEnd->getIntY()-$neighbor->getIntY()));
                    $neighbor->setIntF();
                    $this->_openList[] = $neighbor;
                }
            }
            //如果终点在OpenList中，直接返回终点格子
            //终止运行，到达终点
            if ($this->findInOpenList($NodeEnd)) {
                //寻找父节点
                if($NodeCurrent->getIntX() ==  $NodeStart->getIntX() && $NodeCurrent->getIntY() ==  $NodeStart->getIntY()){
                    //特殊case，起点的下一步就是终点
                    return $NodeEnd;
                }
                while(true){
                    if($NodeCurrent->getIntParentX() ==  $NodeStart->getIntX() && $NodeCurrent->getIntParentY() ==  $NodeStart->getIntY()){
                        break;
                    }
                    /*if($NodeCurrent->getIntX() ==  $NodeStart->getIntX() && $NodeCurrent->getIntY() ==  $NodeStart->getIntY()){
                        //这个地方有可能出现父节点不一致但是回到起点的情况
                        echo '---------miao , miao ,miao~----------';
                        var_dump($NodePro);
                        var_dump($this->_arrParentMap[$NodePro->getIntParentX()][$NodePro->getIntParentY()]);
                        return $NodePro;
                    }*/
                    if(isset($this->_arrParentMap[$NodeCurrent->getIntParentX()][$NodeCurrent->getIntParentY()])) {
                        $NodeFather = $this->_arrParentMap[$NodeCurrent->getIntParentX()][$NodeCurrent->getIntParentY()];
                        if($NodeFather->getIntX() ==  $NodeStart->getIntX() && $NodeFather->getIntY() ==  $NodeStart->getIntY()){
                            //判断父节点的父节点是不是起点，是的话提前退出
                            return new myNode($NodeCurrent->getIntParentX(),$NodeCurrent->getIntParentY());
                        }
                        //echo $NodeCurrent->getIntX() .","  . $NodeCurrent->getIntY() ."\n";
                        //echo $NodeCurrent->getIntParentX() .":,:"  . $NodeCurrent->getIntParentY() ."\n";
                        $NodeCurrent = $this->_arrParentMap[$NodeCurrent->getIntParentX()][$NodeCurrent->getIntParentY()];
                    }
                }
                return $NodeCurrent;
            }
        }
        //OpenList用尽，仍然找不到终点，说明终点不可到达，返回空
        return false;
    }


    public  function  findMinNode(){
        $intMinScore = PHP_INT_MAX;
        $NodeBest = '';
        $NodeBestKey = '';
        foreach($this->_openList as $key => $candidate) {
            if($intMinScore > $candidate->getIntF()) {
                $intMinScore = $candidate->getIntF();
                $NodeBest = $candidate;
                $NodeBestKey = $key;
            }
        }


        if(isset($this->_openList[$NodeBestKey])) {
            // 当前方格节点从open list中移除
            unset($this->_openList[$NodeBestKey]);
            //当前方格节点进入 close list
            $this->_closeList[] = $NodeBest;
        }
        return $NodeBest;
    }


    public  function  findNeighbors($NodeCurrent) {
        $arrNodeNeighbors = array();
        $intMaxX = count($this->_arrMap);
        $intMaxY = count($this->_arrMap[0]);
        if($NodeCurrent->getIntX() >= 1) {
            //左边有格子
            $NodeTmp = new myNode($NodeCurrent->getIntX() -1 ,$NodeCurrent->getIntY());
            if(!$this->checkIsInCloseList($NodeTmp)) {
                $arrNodeNeighbors[] = $NodeTmp;
            }
        }

        if($NodeCurrent->getIntX() <= $intMaxX - 1) {
            //右边有格子
            $NodeTmp = new myNode($NodeCurrent->getIntX() + 1 ,$NodeCurrent->getIntY());
            if(!$this->checkIsInCloseList($NodeTmp)) {
                $arrNodeNeighbors[] = $NodeTmp;
            }
        }


        if($NodeCurrent->getIntY() >= 1) {
            //上边有格子
            $NodeTmp = new myNode($NodeCurrent->getIntX()  ,$NodeCurrent->getIntY() - 1);
            if(!$this->checkIsInCloseList($NodeTmp)) {
                $arrNodeNeighbors[] = $NodeTmp;
            }
        }

        if($NodeCurrent->getIntY() <= $intMaxY - 1) {
            //下边有格子
            $NodeTmp = new myNode($NodeCurrent->getIntX() ,$NodeCurrent->getIntY() + 1);
            if(!$this->checkIsInCloseList($NodeTmp)) {
                $arrNodeNeighbors[] = $NodeTmp;
            }
        }

        unset($NodeTmp);
        return  $arrNodeNeighbors;
    }

    //判断是否在OpenList中
    private function findInOpenList($Node){

        if(empty($this->_openList) || empty($Node)) {
            return false;
        }
        foreach($this->_openList as $open) {
            if($open->getIntX() == $Node->getIntX() && $open->getIntY() == $Node->getIntY()) {
                return true;
            }
        }
        return false;
    }
}

class  myNode
{
    private $_intX;
    private $_intY;
    private $_intParentX;
    private $_intParentY;
    private $_intF;
    private $_intG;
    private $_intH;

    public function __construct($intX, $intY)
    {
        $this->_intX = $intX;
        $this->_intY = $intY;
        $this->_intG = 0;
        $this->_intH = 0;
        $this->_intF = $this->_intG + $this->_intH;
    }

    /**
     * @return the $_intX
     */
    public function getIntX()
    {
        return $this->_intX;
    }

    /**
     * @return the $_intY
     */
    public function getIntY()
    {
        return $this->_intY;
    }

    /**
     * @return the $_intParentX
     */
    public function getIntParentX()
    {
        return $this->_intParentX;
    }

    /**
     * @return the $_intParentY
     */
    public function getIntParentY()
    {
        return $this->_intParentY;
    }

    /**
     * @return the $_intF
     */
    public function getIntF()
    {
        return $this->_intF;
    }

    /**
     * @return the $_intG
     */
    public function getIntG()
    {
        return $this->_intG;
    }

    /**
     * @return the $_intH
     */
    public function getIntH()
    {
        return $this->_intH;
    }

    /**
     * @param field_type $_intX
     */
    public function setIntX($_intX)
    {
        $this->_intX = $_intX;
    }

    /**
     * @param field_type $_intY
     */
    public function setIntY($_intY)
    {
        $this->_intY = $_intY;
    }

    /**
     * @param field_type $_intParentX
     */
    public function setIntParentX($_intParentX)
    {
        $this->_intParentX = $_intParentX;
    }

    /**
     * @param field_type $_intParentY
     */
    public function setIntParentY($_intParentY)
    {
        $this->_intParentY = $_intParentY;
    }

    /**
     * @param number $_intF
     * F = G + H;
     */
    public function setIntF()
    {
        $this->_intF = $this->_intG + $this->_intH;
    }

    /**
     * @param number $_intG
     */
    public function setIntG($_intG)
    {
        $this->_intG = $_intG;
    }

    /**
     * @param number $_intH
     */
    public function setIntH($_intH)
    {
        $this->_intH = $_intH;
    }

}
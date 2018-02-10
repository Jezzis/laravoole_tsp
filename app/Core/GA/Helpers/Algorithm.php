<?php
/**
 * file Algorithm.php 2017/12/4
 *
 * This file is part of the tsp
 *
 * @author Jezzis <jezzis727@126.com>
 * @copyright (c) 2014 - 2017 Yunniao Inc.
 */

namespace App\Core\GA\Helpers;


use App\Core\Common\Helpers\PointFactory;
use App\Core\Common\Helpers\Scene;
use Log;

class Algorithm
{
    const POPULATION_SIZE = 30;
    const CROSSOVER_PROBABILITY = 0.9;
    const MUTATION_PROBABILITY = 0.01;

    const RUNNING_STATUS_RUNNING = 3;
    const RUNNING_STATUS_PAUSE = 2;
    const RUNNING_STATUS_STOP = 1;

    static $UNCHANGED_GENS = 0;

    protected $status = 0;

    /**
     * @var Scene
     */
    protected $scene;

    protected $currentGeneration = 0;

    /**
     * 样本数组(二维),
     *  每个元素代表一个点序号
     *  每行代表一个顺序组合,即一个样本
     * @var array [[int, int, ...], ...]
     */
    protected $populations = [];

    /**
     * 最优样本位置
     * @var
     */
    protected $bestPosition;

    /**
     * 最优样本
     * @var array [int, ...]
     */
    protected $best = [];

    /**
     * 最优样本指标
     * @var int
     */
    protected $bestValue;

    /**
     * 样本的指标数组
     *  用于评价样本优劣
     * @var array [int, ...]
     */
    protected $values = [];

    protected $fitnessValues = [];

    protected $roulette = [];

    protected $mutationTimes = 0;

    public function __construct($width, $height)
    {
        $this->scene = new Scene($width, $height, PointFactory::TYPE_2D);
    }

    public function addRandomPoints($size = 10)
    {
        $this->scene->addRandomPoints($size);
    }

    public function addPoint($x, $y)
    {
        $this->scene->addPoint($x, $y);
    }

    public function clearPoints()
    {
        $this->scene->clearPoints();
    }

    public function getPoints()
    {
        return $this->scene->getPoints();
    }

    public function GAInitialize()
    {
        $this->currentGeneration = 0;
        $this->populations = [];
        $this->best = [];
        $this->bestValue = 0;
        $this->bestPosition = 0;
        $this->mutationTimes = 0;
        $this->values = [];
        $this->fitnessValues = [];
        $this->roulette = [];

        $this->scene->calcDistance();
        $num = $this->scene->getPointCount();
        if ($num < 3)
            return ;

        for ($i = 0; $i < self::POPULATION_SIZE; $i++)
            $this->populations[] = $this->randomIndividual($num);

        Log::debug('init populations: ' . json_encode($this->populations));
        $this->setBestValue();
    }

    public function GANextGeneration()
    {
        if (empty($this->populations))
            $this->GAInitialize();

        $this->currentGeneration++;
        $this->selection();
        $this->crossover();
        $this->mutation();
        $this->setBestValue();
    }

    /**
     * 执行选择
     * @author Jezzis <jezzis727@126.com>
     *
     */
    public function selection()
    {
        Log::debug('in selection...');
        $parents = [];
        $initNum = 4;
        $parents[] = $this->populations[$this->bestPosition];
        $parents[] = $this->doMutate($this->best);
        $parents[] = $this->pushMutate($this->best);
        $parents[] = $this->best;

        $this->setRoulette();
        for ($i = $initNum; $i < self::POPULATION_SIZE; $i++) {
            $parents[] = $this->populations[$this->wheelOut(rand(0, 1000) / 1000.0)];
        }
        $this->populations = $parents;
    }

    /**
     * 杂交
     * @author Jezzis <jezzis727@126.com>
     *
     */
    public function crossover()
    {
        // 按一定比例抽取样本
        $queue = [];
        for ($i = 0; $i < self::POPULATION_SIZE; $i++) {
            if (rand(0, 1) < self::CROSSOVER_PROBABILITY) {
                $queue[] = $i;
            }
        }
        // 打乱顺序
        shuffle($queue);
        // 杂交
        for ($i = 0, $j = count($queue) - 1; $i < $j; $i += 2) {
            $this->doCrossover($queue[$i], $queue[$i + 1]);
        }
    }

    public function doCrossover($x, $y)
    {
        $child1 = $this->getChild('next', $x, $y);
        $child2 = $this->getChild('prev', $x, $y);
        $this->populations[$x] = $child1;
        $this->populations[$y] = $child2;
    }

    /**
     * 从样本组中的指定2个样本中,随机选择一个index作为入口
     *  分别找两个样本中的该index前/后的点,比较两个样本中该index的点与所找到的相邻点谁更优
     *  取更优的点作为新样本的点,更新index为相邻点的index,并删除老index下俩样本的点
     *  重复上一个步骤,直到俩样本为空
     * @author Jezzis <jezzis727@126.com>
     *
     * @param $fun
     * @param $x
     * @param $y
     * @return array
     */
    public function getChild($fun, $x, $y)
    {
//        Log::debug(json_encode(['populations' => $this->populations]));
        $solution = [];
        $px = $this->populations[$x];
        $py = $this->populations[$y];
        $c = $px[random_int(0, count($px) - 1)];
//        Log::debug(json_encode(['px' => $px, 'py' => $py, 'c' => $c]));
        $solution[] = $c;
        while (count($px) > 1) {
            $funcPrefix = 'array_';
            $dx = call_user_func($funcPrefix . $fun, $px, array_index_of($px, $c));
            $dy = call_user_func($funcPrefix . $fun, $py, array_index_of($py, $c));
            array_remove_of($px, $c);
            array_remove_of($py, $c);
//            Log::debug(json_encode(['px' => $px, 'py' => $py, 'c' => $c]));
            $disCdx = $this->scene->getDistance($c, $dx);
            $disCdy = $this->scene->getDistance($c, $dy);
            $c = $disCdx < $disCdy ? $dx : $dy;
            $solution[] = $c;
        }
        return $solution;
    }

    public function mutation()
    {
        for ($i = 0; $i < self::POPULATION_SIZE; $i++) {
            if (rand(0, 1000) / 1000.0 < self::MUTATION_PROBABILITY) {
                if (rand(0, 1) > 0.5) {
                    $this->populations[$i] = $this->pushMutate($this->populations[$i]);
                } else {
                    $this->populations[$i] = $this->doMutate($this->populations[$i]);
                }
                $i--;
            }
        }
    }

    public function preciseMutate($seq)
    {
        if (rand(0, 1) > 0.5) {
            array_reverse($seq);
        }
        $best = $this->evaluate($seq);
        $length = count($seq);
        for ($i = 0; $i < ($length >> 1); $i++) {
            for ($j = $i + 2; $j < $length - 1; $j++) {
                $newSeq = $this->swapSeq($seq, $i, $i + 1, $j, $j + 1);
                $v = $this->evaluate($newSeq);
                if ($v < $best) {
                    $best = $v;
                    $seq = $newSeq;
                }
            }
        }
        return $seq;
    }

    public function preciseMutate1($seq)
    {
        $best = $this->evaluate($seq);
        $length = count($seq);
        for ($i = 0; $i < $length - 1; $i++) {
            array_swap($seq, $i, $i + 1);
            $v = $this->evaluate($seq);
            if ($v < $best) {
                $best = $v;
            }
        }
        return $seq;
    }

    public function swapSeq($seq, $p0, $p1, $q0, $q1)
    {
        $seq1 = array_slice($seq, $p0, $p1);
        $seq2 = array_slice($q0, $q1);
        array_push($seq2, $seq[$p0]);
        array_push($seq2, $seq[$p1]);
        $seq3 = array_slice($seq, $q1, count($seq));
        return array_merge($seq1, $seq2, $seq3);
    }

    /**
     * 随机取中间一段,翻转
     * @author Jezzis <jezzis727@126.com>
     *
     * @param $seq
     * @return mixed
     */
    public function doMutate($seq)
    {
//        Log::debug(json_encode($seq));
        $this->mutationTimes++;
        // m and n refers to the actual index in the array
        // m range from 0 to length-2, n range from 2...length-m
        do {
            $m = rand(0, count($seq) - 2);
            $n = rand(0, count($seq) - 1);
        } while ($m >= $n);
//        Log::debug(json_encode(['m' => $m, 'n' => $n]));

        for ($i = 0, $j = ($n - $m + 1) >> 1; $i < $j; $i++) {
            array_swap($seq, $m + $i, $n - $i);
        }
        return $seq;
    }

    /**
     * 随机取2点,翻转前2段
     * @author Jezzis <jezzis727@126.com>
     *
     * @param $seq
     * @return array
     */
    public function pushMutate($seq)
    {
        $this->mutationTimes++;
        do {
            $m = rand(0, count($seq) >> 1);
            $n = rand(0, count($seq) - 1);
        } while ($m >= $n);

        $s1 = array_slice($seq, 0, $m);
        $s2 = array_slice($seq, $m, $n - $m);
        $s3 = array_slice($seq, $n);
        return array_merge($s2, $s1, $s3);
    }

    public function setBestValue()
    {
        for ($i = 0; $i < count($this->populations); $i++) {
            $this->values[$i] = $this->evaluate($this->populations[$i]);
        }

        $currentBest = $this->getCurrentBest();
        if (empty($bestValue) || $bestValue > $currentBest['bestValue']) {
            $this->bestPosition = $currentBest['bestPosition'];
            $this->best = $this->populations[$currentBest['bestPosition']];
            $this->bestValue = $currentBest['bestValue'];
            self::$UNCHANGED_GENS = 0;
        } else {
            self::$UNCHANGED_GENS += 1;
        }
    }

    /**
     * 获取最优样本
     * @author Jezzis <jezzis727@126.com>
     *
     * @return array
     * [
     *     'bestPosition' => 1, // index
     *     'bestValue' => 1, // value
     * ]
     */
    public function getCurrentBest()
    {
        $bestP = 0;
        $currentBestValue = head($this->values);

        $length = count($this->populations);
        for ($i = 1; $i < $length; $i++) {
            if ($this->values[$i] < $currentBestValue) {
                $currentBestValue = $this->values[$i];
                $bestP = $i;
            }
        }
        return [
            'bestPosition' => $bestP,
            'bestValue' => $currentBestValue
        ];
    }

    public function setRoulette()
    {
        // calculate all the fitness
        Log::debug('values: ' . json_encode($this->values));
        $length = self::POPULATION_SIZE;
        for ($i = 0; $i < $length; $i++) {
            $this->fitnessValues[$i] = 1.0 / $this->values[$i];
        }

        // set the roulette
        $sum = array_sum($this->fitnessValues);
        for ($i = 0; $i < $length; $i++) {
            $this->roulette[$i] = $this->fitnessValues[$i] / $sum;
        }

        for ($i = 1; $i < $length; $i++) {
            $this->roulette[$i] += $this->roulette[$i - 1];
        }
        Log::debug('roulette: ' . json_encode($this->roulette));
    }

    public function wheelOut($rand)
    {
        for ($i = 0; $i < count($this->roulette); $i++) {
//            Log::debug(sprintf('rand: %f, index: %d, value: %f', $rand, $i, $this->roulette[$i]));
            if ($rand <= $this->roulette[$i]) {
//                Log::debug('select ' . $i);
                return $i;
            }
        }
        return 0;
    }

    public function randomIndividual($n)
    {
        $a = [];
        for ($i = 0; $i < $n; $i++) {
            $a[] = $i;
        }
        shuffle($a);
        return $a;
    }

    public function evaluate($individual)
    {
        $sum = $this->scene->getDistance(head($individual), end($individual));
        $length = count($individual);
        for ($i = 1; $i < $length; $i++) {
            $sum += $this->scene->getDistance($individual[$i], $individual[$i - 1]);
        }
        return $sum;
    }

    /**
     * 获取当前最优解决方案
     * @author Jezzis <jezzis727@126.com>
     * @return array
     */
    public function getCurrentBestSolution()
    {
        $bestPosition = $bestValue = null;
        extract($this->getCurrentBest());
        $population = $this->populations[$bestPosition];
        $points = [];
        array_walk($population, function($idx) use (&$points) {
            $points[] = $this->scene->getPoint($idx);
        });
        return [
            'points' => $this->getPoints(),
            'best' => $points,
            'bestValue' => $bestValue,
            'currentGeneration' => $this->currentGeneration,
            'mutationTimes' => $this->mutationTimes,
        ];
    }
}
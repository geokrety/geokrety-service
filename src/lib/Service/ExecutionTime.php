<?php

namespace Service;

// src : https://stackoverflow.com/questions/535020/tracking-the-script-execution-time-in-php
class ExecutionTime {
     private $startTime;
     private $startMicroTime;
     private $endTime;
     private $endMicroTime;

     public function start(){
         $this->startTime = getrusage();
         $this->startMicroTime = microtime(true);
     }

     public function end(){
         $this->endTime = getrusage();
         $this->endMicroTime = microtime(true);
     }

     private function runTime($ru, $rus, $index) {
         return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
     }

     public function durationSec() {
        return round($this->endMicroTime - $this->startMicroTime, 2);
     }

     public function __toString(){
         $diff = $this->durationSec();
         return $this->runTime($this->endTime, $this->startTime, "utime") .
        " ms (computations) - " . $this->runTime($this->endTime, $this->startTime, "stime") .
        " ms (sys calls) - $diff seconds (execution time)";
     }
 }
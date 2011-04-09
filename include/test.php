<?php
require_once 'ErrorManager.php';

function die0() {
  echo 'died in global function';
}

class Call {
  public static function die1() {
    echo 'died in static function';
  }
  public function die2() {
    echo 'died in class function';
  }
}
ErrorManager::  wrt

ErrorManager::SetLogFile('error.log');
ErrorManager::SetLogLevel(E_ALL | E_STRICT, true);
ErrorManager::SetDebug(true, false);//echo debug data and do it between <pre></pre> tags
//different correct implementation of SetDieLevel
//remove or modify exiting levels to let the script execute beyond first error
//ErrorManager::SetDieLevel(E_WARNING, 'die0');
ErrorManager::SetDieLevel(E_WARNING, array('Call', 'die1'));
//ErrorManager::SetDieLevel(E_WARNING, array(new Call(), 'die2'));
//echo 'going to do 1/0 ';
//$a = 1 / 0;
//echo 'going to do incorrect function call for str_replace ';
//$b = str_replace('test', 'test');
?>
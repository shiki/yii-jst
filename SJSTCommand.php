<?php

/**
 * 
 *
 * @author Shiki
 */
class SJSTCommand extends CConsoleCommand
{
  public $componentId = 'jst';
  
  /**
   *
   * @param string $package Package name
   * @param string $out Output file path
   * @throws CException 
   */
  public function actionIndex($package, $out)
  {
    if (!($component = Yii::app()->getComponent($this->componentId)))
      throw new CException("Could not find {$this->componentId} component.");
    
    if (!($compiled = $component->compilePackage($package)))
      throw new CException("Could not compile $package.");
    
    file_put_contents($out, $compiled);
    echo $package . ' >> ' . $out;
    echo PHP_EOL . 'Done' . PHP_EOL;
  }
  
  /**
   * Compile all packages
   * @param string $out Output folder path
   */
  public function actionAll($out)
  {
    if (!($component = Yii::app()->getComponent($this->componentId)))
      throw new CException("Could not find {$this->componentId} component.");
    
    foreach ($component->packages as $packageName => $package) {
      if (!($compiled = $component->compilePackage($packageName)))
        throw new CException("Could not compile $packageName.");
      
      $path = rtrim($out, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $packageName . '.js';
      file_put_contents($path, $compiled);
      echo $packageName. ' >> ' . $path . PHP_EOL;
    }
    
    echo 'Done' . PHP_EOL;
  }
}

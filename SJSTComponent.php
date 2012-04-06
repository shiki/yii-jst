<?php

/**
 * SJSTComponent
 *
 * @author Shiki
 */
class SJSTComponent extends CApplicationComponent
{
  const DEFAULT_JS_CONTAINER = 'JST';
  
  /**
   * Array in this format:
   *   'package-name' => array(
   *     // Javascript object the templates will be attached to. Optional, "JST" by default.
   *     'container' => 'JST',
   *     'basePath' => '<optional>', // base path of views (Yii alias only for now)
   *     // the templates
   *     'views' => array(
   *       'view-path', // Yii alias only for now
   *       'view-name' => 'view-path', // Yii alias only for now
   *       ...
   *     )
   *   ),
   *   ...
   * @var array 
   */
  public $packages = array();
  
  public $wrapInAMD = false;
  
  protected $_viewFilePathMap = array();
  
  /**
   * @param string $packageName 
   * @return array
   */
  public function getViewFilePathMap($packageName)
  {
    if (isset($this->_viewFilePathMap[$packageName]))
      return $this->_viewFilePathMap[$packageName];
    
    if (!isset($this->packages[$packageName]))
      return null;
    
    $package = $this->packages[$packageName];
    $basePath = isset($package['basePath']) ? $package['basePath'] : null;
    
    $map = array();
    foreach ($package['views'] as $key => $view) {
      $viewPath = $this->resolveViewPath($view, $basePath);
      if (!$viewPath)
        return null; // abort
      
      if (is_string($key))
        $map[$key] = $viewPath;
      else
        $map[$view] = $viewPath;
    }
    
    $this->_viewFilePathMap[$packageName] = $map;
    return $map;
  }
  
  /**
   *
   * @param string $packageName 
   * @return string
   */
  public function compilePackage($packageName)
  {
    if (!($map = $this->getViewFilePathMap($packageName)))
      return null;
    
    $package = $this->packages[$packageName];
    
    $container = isset($package['container']) ? $package['container'] : self::DEFAULT_JS_CONTAINER;
    $output = "if (typeof $container === 'undefined') $container = {};";
    foreach ($map as $key => $fullPath) {
      if (!file_exists($fullPath))
        return null;
      
      $text = file_get_contents($fullPath);
      $output .= PHP_EOL . "JST['$key'] = " . json_encode($text) . ';';
    }
    return $this->wrapOutput($output, $container);
  }
  
  /**
   *
   * @param string $view
   * @param string $basePath
   * @return string 
   */
  protected function resolveViewPath($view, $basePath)
  {
    if (!empty($basePath))
      $view = $basePath . '.' . $view;
    
    $ret = Yii::getPathOfAlias($view);
    return $ret === false ? null : $ret . '.php';
  }
  
  /**
   *
   * @param string $output 
   * @return string
   */
  protected function wrapOutput($output, $container)
  {
    if ($this->wrapInAMD) {
      return 'define(function() {' 
        . PHP_EOL . $output 
        . PHP_EOL . 'return ' . $container . ';'
        . PHP_EOL . '});';
    }
    
    return $output;
  }
}

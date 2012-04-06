<?php

/**
 * SJSTAction
 *
 * @author Shiki
 */
class SJSTAction extends CAction
{
  const FORMAT_DATETIME = 'D, d M Y H:i:s';
  
  public $componentId = 'jst';
  
  public $cacheMaxAge = 2592000; // 30 days
  public $allowCache = true;
  
  public function run()
  {
    if (!($component = Yii::app()->getComponent($this->componentId)))
      throw new CHttpException(404, "Could not find {$this->componentId} component.");
    if (!($packageName = Yii::app()->request->getQuery('package')))
      throw new CHttpException(404, 'Package name is required.');
    
    if (!$this->allowCache) {
      echo $this->getCompiled($packageName);
      return;
    }
    
    $headers = self::getHeaders();
    $modifiedTime = $this->getPackageLastModifiedTime($packageName);
    
    if (isset($headers['If-Modified-Since']) 
      && (strtotime($headers['If-Modified-Since']) == $modifiedTime)) {
      header('Last-Modified: ' . gmdate(self::FORMAT_DATETIME, $modifiedTime) .' GMT', true, 304);
      header('Expires: ' . gmdate(self::FORMAT_DATETIME, $modifiedTime + $this->cacheMaxAge) .' GMT');
      header('Cache-Control: public, max-age=' . $this->cacheMaxAge);
    } else {
      $compiled = $this->getCompiled($packageName);
      
      header('Last-Modified: ' . gmdate(self::FORMAT_DATETIME, $modifiedTime) .' GMT', true, 200);
      header('Expires: ' . gmdate(self::FORMAT_DATETIME, $modifiedTime + $this->cacheMaxAge) .' GMT');
      header('Cache-Control: public, max-age=' . $this->cacheMaxAge);
      header('Content-type: application/javascript');
      header_remove('Pragma');
      
      echo $compiled;
    }
  }
  
  /**
   *
   * @param type $packageName
   * @return string
   * @throws CHttpException 
   */
  protected function getCompiled($packageName)
  {
    if (!($compiled = Yii::app()->getComponent($this->componentId)->compilePackage($packageName)))
        throw new CHttpException(404, "Package named $packageName is invalid.");
    return $compiled;
  }
  
  /**
   *
   * @param string $packageName
   * @return int
   * @throws CHttpException 
   */
  protected function getPackageLastModifiedTime($packageName)
  {
    $map = Yii::app()->getComponent($this->componentId)->getViewFilePathMap($packageName);
    $ret = 0;
    foreach ($map as $filePath) {
      if (!file_exists($filePath)) {
        $ret = 0;
        break;
      }
      
      if (($time = filemtime($filePath)) > $ret)
        $ret = $time;
    }
    
    if (empty($ret))
      throw new CHttpException(404, "Package named $packageName is invalid.");
    
    return $ret;
  }
  
  /**
   * Taken from the php-oauth project https://github.com/shiki/php-oauth
   * @return array
   */
  protected static function getHeaders() {
    if (function_exists('apache_request_headers')) {
      // we need this to get the actual Authorization: header
      // because apache tends to tell us it doesn't exist
      $headers = apache_request_headers();

      // sanitize the output of apache_request_headers because
      // we always want the keys to be Cased-Like-This and arh()
      // returns the headers in the same case as they are in the
      // request
      $out = array();
      foreach ($headers AS $key => $value) {
        $key = str_replace(
            " ",
            "-",
            ucwords(strtolower(str_replace("-", " ", $key)))
          );
        $out[$key] = $value;
      }
    } else {
      // otherwise we don't have apache and are just going to have to hope
      // that $_SERVER actually contains what we need
      $out = array();
      if( isset($_SERVER['CONTENT_TYPE']) )
        $out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
      if( isset($_ENV['CONTENT_TYPE']) )
        $out['Content-Type'] = $_ENV['CONTENT_TYPE'];

      foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) == "HTTP_") {
          // this is chaos, basically it is just there to capitalize the first
          // letter of every word that is not an initial HTTP and strip HTTP
          // code from przemek
          $key = str_replace(
            " ",
            "-",
            ucwords(strtolower(str_replace("_", " ", substr($key, 5))))
          );
          $out[$key] = $value;
        }
      }
    }
    return $out;
  }
}

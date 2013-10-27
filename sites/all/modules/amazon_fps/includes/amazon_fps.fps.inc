<?php
module_load_include('class', 'amazon_fps', 'classes/Amazon_FPS_Client_Drupal');

/**
 * Helper function. Provides a reusable Amazon_FPS_Client instance.
 */
function amazon_fps_client($config = array(), $new = FALSE, $reset = FALSE) {
  static $instance;
  $instance_exists = (isset($instance) && $instance instanceof Amazon_FPS_Client_Drupal);
  if (in_array(TRUE, array($new, $reset)) || $instance_exists === FALSE) {
    require_once('Amazon/FPS/Client.php');
  }
  if (($instance_exists === FALSE && $new === FALSE) || $reset) {
    $instance = new Amazon_FPS_Client_Drupal(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, $config);
  }
  elseif ($new) {
    return new Amazon_FPS_Client_Drupal(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, $config);
  }
  return $instance;
}

/**
 * Perform a Pay request with the specified parameters.
 * @param $request See http://docs.amazonwebservices.com/AmazonFPS/latest/FPSAdvancedGuide/Pay.html
 *   for available options.
 * @return Amazon_FPS_Model_PayResponse An instantiated response object.
 */
function amazon_fps_fps_pay($request, $config = array()) {
  return amazon_fps_invoke('pay', $request, $config);
}

/**
 * Perform a VerifySignature request with the specified parameters.
 * @param $request See http://docs.amazonwebservices.com/AmazonFPS/latest/FPSAdvancedGuide/VerifySignatureAPI.html
 *   for available options.
 * @return Amazon_FPS_Model_VerifySignatureResponse An instantiated response object.
 */
function amazon_fps_fps_verify_signature($request, $config = array()) {
  try {
    $response = amazon_fps_invoke('verifySignature', $request, $config);
    return $response;
  }
  catch (Amazon_FPS_Exception $e) {
    if ($e->getErrorCode() == 'InvalidSignature') {
      return FALSE;
    }
  }
}

/**
 * Dynamically invoke the appropriate Amazon_FPS_Client method.
 *
 * This approach allows for consistent environment setup and other
 *   support functions to occur without having to perform them in
 *   every function.
 * @param $method_name The name of the Amazon_FPS_Client method
 *   to call.
 * See the documentation for the specific functions for more information
 *   about the $request and $config parameters.
 */
function amazon_fps_invoke($method_name, $request, $config) {
  _amazon_fps_environment_set();
  try {
    $response = amazon_fps_client($config)->{$method_name}($request);
  }
  catch (Amazon_FPS_Exception $e) {
    // Oh! There was an exception. Re-establish the old environment,
    // and hope that this happens before Drupal traps the error.
    _amazon_fps_environment_set(TRUE);
    _amazon_fps_exception_log($e, $method_name, $request, $config);
    throw $e;
  }
  _amazon_fps_environment_set(TRUE);
  return $response;
}


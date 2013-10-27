<?php
module_load_include('class', 'amazon_fps', 'classes/Amazon_FPS_CBUISingleUsePipeline_Drupal');

/**
 * Wrapper function to instantiate Amazon_FPS_CBUISingleUsePipeline.
 * @param $mandatory An array containing the following keys:
 *   'caller_reference' => An arbitrary string representing the order
 *     or other unique value for this request.
 *   'return_url' => The URL to which to come back after authorization
 *     is completed.
 *   'transaction_amount' => The amount that should be authorized by
 *     the token.
 * @param $optional An array containing the keys of any other
 *   parameters with which to initialize the class.
 * @param $return_url If set to TRUE, the result of 
 *   the Amazon_FPS_CBUISingleUsePipeline::getURL() method against the instantiated class
 *   will be returned instead of the instance itself.
 * @param $cbui_url If not NULL, the URL for the CBUI will be set to it.
 */
function amazon_fps_cbui_single_use($mandatory, $optional, $return_url = FALSE, $cbui_url = NULL) {
  $cbui = new Amazon_FPS_CBUISingleUsePipeline_Drupal(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
  $caller_reference = $mandatory['caller_reference'];
  $return_url = $mandatory['return_url'];
  $transaction_amount = $mandatory['transaction_amount'];
  $cbui->setMandatoryParameters($caller_reference, $return_url, $transaction_amount);
  foreach ($optional as $parameter => $value) {
    $cbui->addParameter($parameter, $value);
  }
  if (isset($cbui_url)) {
    $cbui->setCBUIURL($cbui_url);
  }
  if ($return_url) {
    return $cbui->getURL();
  }
  else {
    return $cbui;
  }
}


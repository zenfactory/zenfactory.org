<?php
module_load_include('class', 'amazon_fps', 'classes/Amazon_FPS_CBUIRecipientTokenPipeline_Drupal');

/**
 * Wrapper function to instantiate Amazon_FPS_CBUIRecipientTokenPipeline.
 * @param $mandatory An array containing the following keys:
 *   'caller_reference' => An arbitrary string representing the order
 *     or other unique value for this request.
 *   'return_url' => The URL to which to come back after authorization
 *     is completed.
 *   'max_fixed_fee' => The maximum fixed fee that will ever be charged.
 *     Set to 0 if not needed.
 *   'max_variable_fee' => The maximum variable (percentage) fee that
 *     will ever be charged. Set to 0 if not needed.
 *   'recipient_pays_fee' => Whether the recipient covers any transaction
 *     fees from Amazon FPS or not. 'True' or 'False'.
 * @param $optional An array containing the keys of any other
 *   parameters with which to initialize the class.
 * @param $return_url If set to TRUE, the result of 
 *   the Amazon_FPS_CBUIRecipientTokenPipeline::getURL() method against the instantiated class
 *   will be returned instead of the instance itself.
 * @param $cbui_url If not NULL, the URL for the CBUI will be set to it.
 */
function amazon_fps_cbui_recipient_token($mandatory, $optional, $return_url = FALSE, $cbui_url = NULL) {
  $cbui = new Amazon_FPS_CBUIRecipientTokenPipeline_Drupal(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
  $caller_reference = $mandatory['caller_reference'];
  $return_url = $mandatory['return_url'];
  $max_fixed_fee = $mandatory['max_fixed_fee'];
  $max_variable_fee = $mandatory['max_variable_fee'];
  $recipient_pays_fee = $mandatory['recipient_pays_fee'];
  $cbui->setMandatoryParameters($caller_reference, $return_url,
    $max_fixed_fee, $max_variable_fee, $recipient_pays_fee);
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


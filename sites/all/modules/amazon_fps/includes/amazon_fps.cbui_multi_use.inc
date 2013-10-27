<?php
module_load_include('class', 'amazon_fps', 'classes/Amazon_FPS_CBUIMultiUsePipeline_Drupal');

/**
 * Wrapper function to instantiate Amazon_FPS_CBUIMultiUsePipeline.
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
 *   the Amazon_FPS_CBUIMultiUsePipeline::getURL() method against the instantiated class
 *   will be returned instead of the instance itself.
 * @param $cbui_url If not NULL, the URL for the CBUI will be set to it.
 */
function amazon_fps_cbui_multi_use($mandatory, $optional, $return_url = FALSE,
  $cbui_url = NULL, $usage_limit_1 = NULL, $usage_limit_2 = NULL,
  $recipient_cobranding = FALSE, $recipient_tokens = array()) {
  $cbui = new Amazon_FPS_CBUIMultiUsePipeline_Drupal(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
  $caller_reference = $mandatory['caller_reference'];
  $return_url = $mandatory['return_url'];
  $global_amount_limit = $mandatory['global_amount_limit'];
  $cbui->setMandatoryParameters($caller_reference, $return_url, $global_amount_limit);
  $limits = array(1, 2);
  foreach ($limits as $l) {
    $limit_var = "usage_limit_{$l}";
    $function_name = "setUsageLimit{$l}";
    $limit_var_value = $$limit_var;
    if (isset($$limit_var)) {
      if (!isset($limit_var_value['period'])) {
        $limit_var_value['period'] = NULL;
      }
      $cbui->$function_name($limit_var_value['type'], $limit_var_value['value'],
        $limit_var_value['period']);
    }
  }
  if ($recipient_cobranding || !empty($recipient_tokens)) {
    $cbui->setRecipientTokenList($recipient_cobranding, $recipient_tokens);
  }
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


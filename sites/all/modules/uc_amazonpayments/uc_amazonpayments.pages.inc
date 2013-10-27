<?php

/**
 * @file
 * Amazon Payments administration menu items.
 */

/**
 * Process Instant Payment Notifiations from Amazon Payments.
 */
function uc_amazonpayments_ipn() {
  if (!isset($_POST['callerReference'])) {
    watchdog('uc_amazonpayments', 'IPN attempted with invalid order ID.', array(), WATCHDOG_ERROR);
    return;
  }

  if (strpos($_POST['callerReference'], '-') > 0) {
    list($order_id, $cart_id) = explode('-', $_POST['callerReference']);

    // Sanitize order ID and cart ID
    $order_id = intval($order_id);
    $cart_id  = check_plain($cart_id);

    if (!empty($cart_id)) {
      // Needed later by uc_complete_sale to empty the correct cart
      $_SESSION['uc_cart_id'] = $cart_id;
    }
  }
  else {
    $order_id = intval($_POST['callerReference']);
  }

  watchdog('uc_amazonpayments', 'Receiving IPN at URL for order @order_id. <pre>@debug</pre>', array('@order_id' => $order_id, '@debug' => variable_get('uc_amazonpayments_fps_debug_ipn', FALSE) ? print_r($_POST, TRUE) : ''));

  $order = uc_order_load($order_id);

  if ($order == FALSE) {
    watchdog('uc_amazonpayments', 'IPN attempted for non-existent order @order_id.', array('@order_id' => $order_id), WATCHDOG_ERROR);
    return;
  }

  // Assign posted variables to local variables
  $payment_status = check_plain($_POST['transactionStatus']);
  $payment = explode(' ', check_plain($_POST['transactionAmount']));
  $payment_amount = $payment[1];
  $payment_currency = $payment[0];
  // @todo: Can this be found out?
  // $payment_fee = check_plain($_POST['mc_fee']);
  $receiver_email = check_plain($_POST['recipientEmail']);
  if ($receiver_email == '') {
    watchdog('uc_amazonpayments', 'receiverEmail is missing in IPN.', array(), WATCHDOG_ERROR);
    return;
  }
  $txn_id = check_plain($_POST['transactionId']);
  $txn_type = check_plain($_POST['operation']);
  $payer_email = check_plain($_POST['buyerEmail']);

  // @todo: Verify the request FPS-style.
  $ipn_valid = uc_amazonpayments_verify_signature();

  if ($ipn_valid) {
    watchdog('uc_amazonpayments', 'IPN transaction verified.');

    $duplicate = (bool) db_query_range('SELECT 1 FROM {uc_payment_amazonpayments_ipn} WHERE txn_id = :id AND status <> :status', 0, 1, array(':id' => $txn_id, ':status' => 'Pending'))->fetchField();
    if ($duplicate) {
      if ($order->payment_method != 'credit') {
        watchdog('uc_amazonpayments', 'IPN transaction ID has been processed before.', array(), WATCHDOG_NOTICE);
      }
      return;
    }

    db_insert('uc_payment_amazonpayments_ipn')
      ->fields(array(
        'order_id' => $order_id,
        'txn_id' => $txn_id,
        'txn_type' => $txn_type,
        'mc_gross' => $payment_amount,
        'status' => $payment_status,
        'receiver_email' => $receiver_email,
        'payer_email' => $payer_email,
        'received' => REQUEST_TIME,
      ))
      ->execute();

    switch ($payment_status) {
      case 'CANCELLED':
        // Not sure if this is the same as PayPal's...
        // @todo: Double-check what this status means.
        /* uc_order_comment_save($order_id, 0, t('Amazon Payments has canceled the reversal and returned !amount !currency to your account.', array('!amount' => uc_currency_format($payment_amount, FALSE), '!currency' => $payment_currency)), 'admin'); */
        break;

      case 'SUCCESS':
        if (abs($payment_amount - $order->order_total) > 0.01) {
          watchdog('uc_amazonpayments', 'Payment @txn_id for order @order_id did not equal the order total.', array('@txn_id' => $txn_id, '@order_id' => $order->order_id), WATCHDOG_WARNING, l(t('view'), 'admin/store/orders/'. $order->order_id));
        }
        $comment = t('Amazon Payments transaction ID: @txn_id', array('@txn_id' => $txn_id));
        uc_payment_enter($order_id, 'amazonpayments_fps', $payment_amount, $order->uid, NULL, $comment);
        uc_cart_complete_sale($order);
        uc_order_comment_save($order_id, 0, t('Payment of @amount @currency submitted through Amazon Payments.', array('@amount' => uc_currency_format($payment_amount, FALSE), '@currency' => $payment_currency)), 'order', 'payment_received');
        uc_order_comment_save($order_id, 0, t('Amazon Payments IPN reported a payment of @amount @currency.', array('@amount' => uc_currency_format($payment_amount, FALSE), '@currency' => $payment_currency)));
        break;

      case 'FAILURE':
        uc_order_comment_save($order_id, 0, t("The customer's attempted payment from a bank account failed."), 'admin');
        break;

      case 'PENDING':
        // Skip the IPN if SUCCESS came before PENDING (it can happen)
        if (!in_array($order->status, array('payment_received', 'completed'))) {
          uc_order_update_status($order_id, 'amazonpayments_pending');
          uc_order_comment_save($order_id, 0, t('Payment is pending at Amazon Payments: @reason', array('@reason' => check_plain($_POST['statusMessage']))), 'admin');
        }
        break;

      case 'RESERVED':
        // @todo: Make this work when Reserve-based operations are implemented.
        break;
    }
  }
  elseif ($ipn_valid === FALSE) {
    watchdog('uc_amazonpayments', 'IPN transaction failed verification.', array(), WATCHDOG_ERROR);
    uc_order_comment_save($order_id, 0, t('An IPN transaction failed verification for this order.'), 'admin');
  }
}

// Handles a complete Flexible Payments Service sale.
function uc_amazonpayments_complete($order) {
  // Verify that Amazon Payments issued the redirect to the completion URL.
  $fps_config = array('ServiceURL' => _uc_amazonpayments_fps_api_url());
  $payment_authorized = uc_amazonpayments_verify_signature();
  if ($payment_authorized) {
    amazon_fps_include('fps');
    // Issue a Pay request to Amazon Payments. This will also transmit an IPN
    // to the site.
    // Build the request to send to the Amazon FPS API
    $request = array(
      'CallerReference' => check_plain($_GET['callerReference']),
      'SenderTokenId' => check_plain($_GET['tokenID']),
      'TransactionAmount' => array(
        'CurrencyCode' => 'USD',
        'Value' => $order->order_total,
      ),
      'OverrideIPNURL' => url('uc_amazonpayments/ipn',
        array('absolute' => TRUE)),
    );
    $fps = amazon_fps_fps_pay($request, $fps_config);
  }
  _uc_amazonpayments_complete($order, $payment_authorized);
}

/**
 * Perform session checking and cart clearing. Having this as its
 *   own function lets integrating modules reuse this logic.
 */
function _uc_amazonpayments_complete($order, $payment_authorized = TRUE) {
  // If the order ID specified in the return URL is not the same as the one in
  // the user's session, we need to assume this is either a spoof or that the
  // user tried to adjust the order on this side while at Amazon Payments. If it was a
  // legitimate checkout, the IPN will still come in from Amazon Payments so the order
  // gets processed correctly. We'll leave an ambiguous message just in case.
  // We also do this if verification of the request fails.
  if (intval($_SESSION['cart_order']) != $order->order_id || $payment_authorized === FALSE) {
    drupal_set_message(t('Thank you for your order! Amazon Payments will notify us once your payment has been processed.'));
    drupal_goto('cart');
  }

  // Ensure the payment method is Amazon Payments FPS.
  if ($order->payment_method != 'amazonpayments_fps') {
    drupal_goto('cart');
  }

  // This lets us know it's a legitimate access of the complete page.
  $_SESSION['uc_checkout'][$_SESSION['cart_order']]['do_complete'] = TRUE;
  drupal_goto('cart/checkout/complete');
}

// Handles a canceled Flexible Payments Service sale.
function uc_amazonpayments_cancel() {
  unset($_SESSION['cart_order']);

  drupal_set_message(t('Your Amazon Payments payment was canceled. Please feel free to continue shopping or contact us for assistance.'));

  drupal_goto(variable_get('uc_amazonpayments_fps_cancel_return_url', 'cart'));
}

/**
 * Translate the CBUI URL into the FPS URL.
 */
function _uc_amazonpayments_fps_api_url() {
  $url_map = array(
    'https://authorize.payments-sandbox.amazon.com/cobranded-ui/actions/start' => 'https://fps.sandbox.amazonaws.com',
    'https://authorize.payments.amazon.com/cobranded-ui/actions/start' => 'https://fps.amazonaws.com',
  );
  // Longest array key element name/function ever
  return $url_map[variable_get('uc_amazonpayments_fps_server', 'https://authorize.payments-sandbox.amazon.com/cobranded-ui/actions/start')];
}


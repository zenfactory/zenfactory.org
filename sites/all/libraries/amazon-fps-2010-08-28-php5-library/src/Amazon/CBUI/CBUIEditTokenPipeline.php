<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     Amazon_FPS
 *  @copyright   Copyright 2008-2011 Amazon Technologies, Inc.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2010-08-28
 */
/******************************************************************************* 
 *    __  _    _  ___ 
 *   (  )( \/\/ )/ __)
 *   /__\ \    / \__ \
 *  (_)(_) \/\/  (___/
 * 
 *  Amazon FPS PHP5 Library
 * 
 */
 
require_once('CBUIPipeline.php');

class Amazon_FPS_CBUIEditTokenPipeline extends Amazon_FPS_CBUIPipeline {

    /**
     * @param string $accessKeyId    Amazon Web Services Access Key ID.
     * @param string $secretAccessKey   Amazon Web Services Secret Access Key.
     */
    function Amazon_FPS_CBUIEditTokenPipeline($awsAccessKey, $awsSecretKey) {
        parent::Amazon_FPS_CBUIPipeline("EditToken", $awsAccessKey, $awsSecretKey);
    }

    /**
     * Set mandatory parameters required for edit token pipeline.
     */
    function setMandatoryParameters($callerReference, $returnUrl, $tokenId) {
        $this->addParameter("callerReference", $callerReference);
        $this->addParameter("returnURL", $returnUrl);
        $this->addParameter("tokenId", $tokenId);
    }

    function validateParameters($parameters) {
        //mandatory parameters for single use pipeline
        if (!isset($parameters["tokenId"])) {
            throw new Exception("tokenId is missing in parameters.");
        }
    }

}

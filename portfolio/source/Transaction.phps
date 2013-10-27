<?php

Class Transaction 
{
	## Member variables ##
	private $validCardTypes = Array("visa", "mastercard", "discover");
	private $request = null;
	private $soapRequest = null;
	private $donationData = null;
	private $responseData = null;
	private $requestXML = null;
	private $inputErrors = null;
	private $transactionStatus = false;
	private $db = null;
	
	## Member Functions ##

	# Constructor
	public function __construct($formFields)
	{
		$this->db = $GLOBALS['db'];
		if (!empty($formFields))
		{
			$this->donationData = $formFields;
			//$this->donationData['cardmask'] = preg_replace('/.*/', 'X', substr($this->donationData['cardnumber'], strlen($this->donationData['cardnumber'])-4)).substr($this->donationData['cardnumber'], -4);
			$this->donationData['cardmask'] = substr($this->donationData['cardnumber'], 0, 1).preg_replace('/[0-9]/', 'X', substr($this->donationData['cardnumber'], 1, strlen($string)-5)).substr($this->donationData['cardnumber'], -4);
		}
	}

	# Validate Fields	
	public function validateDonation()
	{
		# Donation amount
		if ($this->donationData['donationamount'] <= 0)
		{
			$errorFields[] = "donationamount";	
		}

		# Card Type
		if (empty($this->donationData['cardtype']) || !in_array($this->donationData['cardtype'], $this->validCardTypes))
		{
			$errorFields[] = "cardtype";	
		}

		# Card Number
		if (!preg_match('/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$/',$this->donationData['cardnumber']))
		{
			$errorFields[] = "cardnumber";	
		}

		# Card CVV 
		if (!preg_match('/^[0-9]{3,5}$/',$this->donationData['cvvcode']))
		{
			$errorFields[] = "cvvcode";	
		}

		# Card Expiration 
		if (empty($this->donationData['expmonth']) || empty($this->donationData['expyear']))
		{
			$errorFields[] = "expmonth";	
			$errorFields[] = "expyear";	
		}
		elseif (mktime(0, 0, 0, date("n"), 1, date("Y")) > mktime(0, 0, 0, $this->donationData['expmonth'], 1, $this->donationData['expyear']))
		{
			$errorFields[] = "expmonth";	
			$errorFields[] = "expyear";	
		}

		# First Name
		if (empty($this->donationData['firstname']))
		{
			$errorFields[] = "firstname";	
		}

		# Last Name
		if (empty($this->donationData['lastname']))
		{
			$errorFields[] = "lastname";	
		}

		# Street Address
		if (!preg_match('/^[ 0-9A-Za-z,.#]+$/', $this->donationData['address1']))
		{
			$errorFields[] = "address1";	
		}

		# City 
		if (empty($this->donationData['city']))
		{
			$errorFields[] = "city";	
		}

		# Country 
		if (empty($this->donationData['country']))
		{
			$errorFields[] = "country";	
		}

		# State 
		if (empty($this->donationData['state']) && ($this->donationData['country'] == "US" || $this->donationData['country'] == "CA"))
		{
			$errorFields[] = "state";	
		}

		# Zip 
		if (!preg_match('/^[0-9]{5}$/', $this->donationData['zip']))
		{
			$errorFields[] = "zip";	
		}

		# Email 
		if (!preg_match('/[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}/', $this->donationData['email']))
		{
			$errorFields[] = "email";	
		}
		$this->errors['inputErrors'] = $errorFields;

		# Return
		if (empty($errorFields))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	# Build XML Request
	private function buildRequestXML()
	{
		$this->requestXML= 
		'
		<fdggwsapi:FDGGWSApiOrderRequest xmlns:fdggwsapi="http://secure.linkpt.net/fdggwsapi/schemas_us/fdggwsapi">
			<v1:Transaction xmlns:v1="http://secure.linkpt.net/fdggwsapi/schemas_us/v1">
				<v1:CreditCardTxType>
					<v1:Type>sale</v1:Type>
				</v1:CreditCardTxType>
				<v1:CreditCardData>
					<v1:CardNumber>'.$this->donationData['cardnumber'].'</v1:CardNumber>
					<v1:ExpMonth>'.$this->donationData['expmonth'].'</v1:ExpMonth>
					<v1:ExpYear>'.$this->donationData['expyear'].'</v1:ExpYear>
				</v1:CreditCardData>
				<v1:Payment>
					<v1:ChargeTotal>'.$this->donationData['donationamount'].'</v1:ChargeTotal>
				</v1:Payment>
				<v1:Billing>
					<v1:CustomerID></v1:CustomerID>
					<v1:Name>'.$this->donationData['firstname']." ".$this->donationData['lastname'].'</v1:Name>
					<v1:Company></v1:Company>
					<v1:Address1>'.$this->donationData['address1'].'</v1:Address1>
					<v1:Address2>'.$this->donationData['address2'].'</v1:Address2>
					<v1:City>'.$this->donationData['city'].'</v1:City>
					<v1:State>'.$this->donationData['state'].'</v1:State>
					<v1:Zip>'.$this->donationData['zip'].'</v1:Zip>
					<v1:Country>'.$this->donationData['country'].'</v1:Country>
					<v1:Phone></v1:Phone>
					<v1:Fax></v1:Fax>
					<v1:Email>'.$this->donationData['email'].'</v1:Email>
				</v1:Billing>
			</v1:Transaction>
		</fdggwsapi:FDGGWSApiOrderRequest>';

	}

	# Build SOAP Request
	private function buildSoapRequest()
	{
		$this->soapRequest = 
		'<?xml version="1.0" encoding="UTF-8"?>
		<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
		<SOAP-ENV:Header />
		<SOAP-ENV:Body>'.$this->requestXML.'</SOAP-ENV:Body>
		</SOAP-ENV:Envelope>';
	}

	# Send Request
	public function processDonation()
	{
		# Buld XML
		$this->buildRequestXML();
		$this->buildSoapRequest();

		# Make request
		$authCreds = FIRST_DATA_API_USER.":".FIRST_DATA_API_PW;
		$authString = base64_encode($authCreds);
		$headers = array("Content-Type: text/xml", "Authorization: Basic $authString");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, FIRST_DATA_URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $authCreds);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->soapRequest);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSLCERT, FIRST_DATA_CERT);
		curl_setopt($ch, CURLOPT_SSLKEY, FIRST_DATA_KEY);
		curl_setopt($ch, CURLOPT_SSLKEYPASSWD, FIRST_DATA_KEY_PW);
		$this->response = curl_exec($ch);
	}
	
	# Parse Response
	public function parseResponse()
	{
		# Put response in SimpleXMLElement Object
		try
		{
			$responseObj = new SimpleXMLElement($this->response);

			# Get the name spaces
			$namespaces = $responseObj->getDocNamespaces(1);

			# Check for fault response
			foreach ($responseObj->children($namespaces['SOAP-ENV'])->Body->children($namespaces['SOAP-ENV']) as $child)
			{
				# If there is a fault object in the body something went wrong
				if ($child->getName() == "Fault")
				{
					$fault = $child->children();
					$this->responseData['faultcode'] = (string)$fault->faultcode;
					$this->responseData['faultstring'] = (string)$fault->faultstring;
					$this->responseData['errorDetails'] = (string)$fault->detail;
					$this->transactionStatus = false;
				}
			}

			# No fault, get repsonse data
			if (in_array("fdggwsapi", array_keys($namespaces)))
			{
				$responseBodyData = $responseObj->children($namespaces['SOAP-ENV'])->Body->children($namespaces['fdggwsapi'])->FDGGWSApiOrderResponse;
				$this->responseData['TransactionTime'] = (string)$responseBodyData->TransactionTime;
				$this->responseData['TransactionID'] = (string)$responseBodyData->TransactionID;
				$this->responseData['ProcessorReferenceNumber'] = (string)$responseBodyData->ProcessorReferenceNumber;
				$this->responseData['ProcessorResponseMessage'] = (string)$responseBodyData->ProcessorResponseMessage;
				$this->responseData['ErrorMessage'] = (string)$responseBodyData->ErrorMessage;
				$this->responseData['OrderId'] = (string)$responseBodyData->OrderId;
				$this->responseData['ApprovalCode'] = (string)$responseBodyData->ApprovalCode;
				$this->responseData['AVSResponse'] = (string)$responseBodyData->AVSResponse;
				$this->responseData['TDate'] = (string)$responseBodyData->TDate;
				$this->responseData['TransactionResult'] = (string)$responseBodyData->TransactionResult;
				$this->responseData['ProcessorResponseCode'] = (string)$responseBodyData->ProcessorApprovalCode;
				$this->responseData['ProcessorApprovalCode'] = (string)$responseBodyData->ProcessorApprovalCode;
				$this->responseData['TransactionScore'] = (string)$responseBodyData->TransactionScore;
				$this->responseData['FraudAction'] = (string)$responseBodyData->FraudAction;
				$this->responseData['AuthenticationResponseCode'] = (string)$responseBodyData->AuthenticationResponseCode;
				if ($this->responseData['TransactionResult'] == 'APPROVED')
				{
					$this->transactionStatus = true;
				}
				else
				{
					$this->transactionStatus = false;
				}
			}
		}
		catch (Exception $e)
		{
			$this->transactionStatus = false;
		}
	}
	
	public function recordTransaction()
	{
		# Record the transaction details
		$sql = sprintf("INSERT INTO 
							donations	
								(
								FirstName,
								LastName,
								DonationAmount,
								CardType,
								CardMask,
								Address1,
								Address2,
								City,
								Country,
								State,
								Zip,
								Email,
								TransactionType, 
								TransactionTime, 
								TransactionID, 
								ProcessorReferenceNumber,
								ProcessorResponseMessage,
								ErrorMessage,
								OrderId,
								ApprovalCode,
								AVSResponse,
								TDate,
								TransactionResult,
								ProcessorResponseCode,
								ProcessorApprovalCode,
								TransactionScore,
								FraudAction,
								AuthenticationResponseCode,
								faultCode,
								faultString,
								errorDetails)
						VALUES
								('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s', '%s', '%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
							pg_escape_string($this->donationData['firstname']),
							pg_escape_string($this->donationData['lastname']),
							pg_escape_string($this->donationData['donationamount']),
							pg_escape_string($this->donationData['cardtype']),
							pg_escape_string($this->donationData['cardmask']),
							pg_escape_string($this->donationData['address1']),
							pg_escape_string($this->donationData['address2']),
							pg_escape_string($this->donationData['city']),
							pg_escape_string($this->donationData['country']),
							pg_escape_string($this->donationData['state']),
							pg_escape_string($this->donationData['zip']),
							pg_escape_string($this->donationData['email']),
							pg_escape_string("sale"),
							pg_escape_string($this->responseData['TransactionTime']),
							pg_escape_string($this->responseData['TransactionID']),
							pg_escape_string($this->responseData['ProcessorReferenceNumber']),
							pg_escape_string($this->responseData['ProcessorResponseMessage']),
							pg_escape_string($this->responseData['ErrorMessage']),
							pg_escape_string($this->responseData['OrderId']),
							pg_escape_string($this->responseData['ApprovalCode']),
							pg_escape_string($this->responseData['AVSResponse']),
							pg_escape_string($this->responseData['TDate']),
							pg_escape_string($this->responseData['TransactionResult']),
							pg_escape_string($this->responseData['ProcessorResponseCode']),
							pg_escape_string($this->responseData['ProcessorApprovalCode']),
							pg_escape_string($this->responseData['TransactionScore']),
							pg_escape_string($this->responseData['FraudAction']),
							pg_escape_string($this->responseData['AuthenticationResponseCode']),
							pg_escape_string($this->responseData['faultCode']),
							pg_escape_string($this->responseData['faultString']),
							pg_escape_string($this->responseData['errorDetails']));

		if ($this->db->exec($sql))
		{
			return true;
		}
		return false;
	
	}

	# Parse field errors from server
	public function parseErrors()
	{
		# A Fault was returned (probably bad input data)
		if (!empty($this->responseData['errorDetails']))
		{
			$errorString = trim($this->responseData['errorDetails']);
			$errorStrings = explode("\n", $errorString);
			foreach ($errorStrings as $string)
			{
				preg_match("/'v1:([A-Za-z]+)'/", $string, $errorFields);
				if (count($errorFields))
				{
					$this->errors->inputErrors[] = strtolower($errorFields[1]);
				}
			}
			$this->errors['faultCode'] = $this->responseData['faultCode'];
			$this->errors['faultString'] = $this->responseData['faultString'];
			$this->errors['errorDetails'] = $this->responseData['errorDetails'];
		}
		# No fault returned check for error code in response
		else
		{
			$this->errors['errorMessage'] = "We are so sorry! We were unable to accept your donation at this time. Please contact us at veda@vedaproject.org and we'd be happy to accept your donation the old fashioned way!";
		}
	}

	# Make Donation
	public function postDonation()
	{
		$this->processDonation();
		$this->parseResponse();
		if ($this->transactionStatus)
		{
			$this->recordTransaction();
		}
		else
		{
			$this->parseErrors();
		}
		return $this->transactionStatus;
	}

	# Returns array of errors
	public function getErrors()
	{
		return $this->errors;
	}
}

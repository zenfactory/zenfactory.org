<?php

class CloudInterface
{
	private $host;
	private $login;
	private $signature;
	private $hashstring;
	private $headers;
	private $hash;
	private $uri;
	private $requestMethod;
	private $date;
	private $ch;

	function __construct($host, $login, $secret)
	{
		$this->host = $host;
		$this->login = $login;
		$this->secret = $secret;
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
	}

	# Reset Hash
	private function resetHash()
	{
		$this->hash = hash_init("sha1", HASH_HMAC, base64_decode($this->secret));
	}

	# Set Current date
	private function setDate()
	{
		$this->date = gmdate('r');
	}

	# Set request method
	private function setRequestMethod($method)
	{
		$this->requestMethod = strtoupper($method);
	}

	# Set URI
	private function setURI($uri)
	{
		$this->uri = $uri;
		curl_setopt($this->ch, CURLOPT_URL, $this->host.$this->uri);
	}

	# Calculate signature
	private function setHeaders($additionalHeaders = false)
	{
		$this->setDate();
		$this->resetHash();
		$hashstring = "{$this->requestMethod}\napplication/octet-stream\n\n{$this->date}\n".strtolower($this->uri)."\nx-emc-uid:{$this->login}";
		hash_update($this->hash, $hashstring);
		$this->signature = base64_encode(hash_final($this->hash, TRUE));
		$this->headers = array("Content-Type: application/octet-stream", "accept: */*", "x-emc-uid: $this->login", "x-emc-signature: $this->signature", "Date: $this->date");
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers); 
	}

	# Get objs in a dir
	public function ls($dirPath)
	{
		$this->setRequestMethod("GET");
		$this->setURI($dirPath);
		$this->setHeaders();

		# Get list of images
		$result = curl_exec($this->ch);

		# Create simpleXML obj
		$lsXML = new SimpleXMLElement($result);
		foreach ($lsXML->DirectoryList->DirectoryEntry as $obj)
		{
			$objs[] = (string)$obj->Filename;	
		}

		# Return
		return $objs;
	}

	# Get Obj
	public function getObj($path)
	{
		$this->setRequestMethod("GET");
		$this->setURI($path);
		$this->setHeaders();

		return curl_exec($this->ch);
	}

	# Dump Obj
	public function dumpObj($path)
	{
		$this->setRequestMethod("GET");
		$this->setURI($path);
		$this->setHeaders();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, FALSE);
		curl_exec($this->ch);
	}
}

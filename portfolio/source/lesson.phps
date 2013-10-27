<?php

require_once("classes/resources/Lesson.php");
$lesson = new Lesson();

switch (strtolower($this->request->getMethod()))
{
	case 'get':
        if ($lesson->loadFromUri($this->request->getUri()))
		{
			$lesson->buildXML();
			$this->response->setPayload($lesson->getXML());
            $this->response->setContentType("text/xml");
			$this->setStatus(true);
			break;
		}
		$this->setStatus(false);
		break;
	case 'put':
	case 'post':
		$payload = $this->request->getPayload();
		if ($lesson->loadFromPayload($payload,$this->request->getURI()))
		{
			if ($lesson->save())
			{
				$this->response->setPayload("Success.");
				$this->response->setContentType("text/xml");
				$this->setStatus(true);
				break;
			}
			array_push($GLOBALS['ERROR'],"Fatal Error.  Unable to save lesson in lesson switch.");
			break;
		}
		array_push($GLOBALS['ERROR'],"Fatal Error.  Failure to load from payload.");
		$this->response->setContentType("text/xml");
		$this->setStatus(false);
		break;
	case 'delete':
		$lesson->loadFromUri($this->request->getURI());
		if ($lesson->delete())
		{
			$this->setStatus(true);
			break;
		}
		$this->setStatus(false);
		break;
}
?>

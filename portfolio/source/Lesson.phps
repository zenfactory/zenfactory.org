<?php

Class Lesson
{
	########################################################
	#### Member Variables ##################################
	########################################################

	private $id = null;
	private $sectionId = null;
	private $sectionName = null;
	private $content = null;
	private $name = null;
	private $ilos = array();
	private $ilosIntact = null;
	private $description = null;
	private $active = null;
    private $path = null;

	########################################################
	#### Constructor and main function #####################
	########################################################

	# Constructor
	public function __construct()
	{
		# Get DB handle
		$this->db = $GLOBALS['db'];

		# ILO's are not present by default
		$this->ilosIntact = false;
	}


	########################################################
	#### Helper functions for loading object ###############
	########################################################

	# Get name from path
	public function getNameFromPath($path)
	{
		$path = trim($path, "/");
		$tmp = explode("/", $path);
		return $tmp[LESSON_INDEX];
	}

	# Load from path
	public function loadFromUri($uri)
	{
		if (!empty($uri))
		{
			$uri= trim($uri, "/");
			$uriArr = explode("/", $uri);
			if (count($uriArr))
			{
				$query = sprintf("SELECT * FROM lesson WHERE name = '%s' AND section_id = (SELECT id FROM section WHERE name = '%s' AND course_id = (SELECT id FROM course WHERE name = '%s' AND subject_id = (SELECT id FROM subject WHERE name = '%s' AND field_id = (SELECT id FROM field WHERE name = '%s'))))", pg_escape_string($uriArr[LESSON_INDEX]), pg_escape_string($uriArr[SECTION_INDEX]), pg_escape_string($uriArr[COURSE_INDEX]), pg_escape_string($uriArr[SUBJECT_INDEX]), pg_escape_string($uriArr[FIELD_INDEX]));
				$result = $this->db->query($query);
				if ($result)
				{
					$row = $result->fetch(PDO::FETCH_ASSOC);
					$this->id = $row['id'];
					$this->sectionId = $row['section_id'];
					$this->name = $row['name'];
					$this->description = $row['description'];
					$this->content = stripslashes($row['content']);
					$this->active = ($row['active'] == "t") ? true : false;
					return true;
				}
				
			}
		}
		return false;
	}

	# Load by ID
	public function loadById($id)
	{
		if (!empty($id))
		{
			$query = sprintf("SELECT * FROM lesson WHERE id = %s", pg_escape_string($id));
			$results = $this->db->query($query);
			if ($results)
			{
				$row = $results->fetch(PDO::FETCH_ASSOC);
				$this->id = $row['id'];
				$this->sectionId = $row['section_id'];
				$this->name= $row['name'];
				$this->content = $row['content'];
			}
		}	
	}

	# Load by name
	public function loadByName($name)
	{
		$sql = sprintf("SELECT 
							lesson.* 
						FROM 
							lesson
							INNER JOIN section ON (lesson.section_id = section.id)
						WHERE
							lesson.name = '%s'", pg_escape_string($name)
					);
		$result = $this->db->query($sql);
		if ($result)
		{
			$row = $result->fetch(PDO::FETCH_ASSOC);
			$this->id = $row['id'];
			$this->sectionId= $row['section_id'];
			$this->name= $row['name'];
			$this->description = $row['description'];
			$this->active = ($row['active'] == "t") ? true : false;
			$this->content = $row['content'];
		}
	}

	# Load object vars from payload
	public function loadFromPayload($payload,$path)
	{
		try
		{
			$payloadObj = new SimpleXMLElement($payload);
            
            $uri= trim($path, "/");
			$uriArr = explode("/", $uri);
            
            $query = sprintf("SELECT id FROM section WHERE name = '%s' AND course_id = (SELECT id FROM course WHERE name = '%s' AND subject_id = (SELECT id FROM subject WHERE name = '%s' AND field_id = (SELECT id FROM field WHERE name = '%s')))", pg_escape_string($uriArr[SECTION_INDEX]), pg_escape_string($uriArr[COURSE_INDEX]), pg_escape_string($uriArr[SUBJECT_INDEX]), pg_escape_string($uriArr[FIELD_INDEX]));
			$result = $this->db->query($query);
            if ($result)
            {
                $row = $result->fetch(PDO::FETCH_ASSOC);
                $this->sectionId = (int)$row['id'];
                $query = sprintf("SELECT id FROM lesson WHERE name = '%s' AND section_id =%s",pg_escape_string($uriArr[LESSON_INDEX]),pg_escape_string($this->sectionId));
                $result = $this->db->query($query);
                if($result)
                {
                    $row=$result->fetch(PDO::FETCH_ASSOC);
                    $this->id=(int)$row['id'];
                }
            }
            else
            {
                array_push($GLOBALS['ERROR'],"Fetch section ID Failed in Lesson:loadFromPayload");
            }
			$this->name = (string)$payloadObj->name;
			$this->description = (string)$payloadObj->description;
			$this->content = (string)$payloadObj->content;
			$this->active = ((string)$payloadObj->active == "true") ? true : false;
			$this->ilosIntact = true;
			$this->path=$path;
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	# Build xml
	public function buildXML()
	{
		$this->xml = "<lesson><id>{$this->id}</id><sectionId>{$this->sectionId}</sectionId><name>{$this->name}</name><description>{$this->description}</description><content>".htmlentities($this->content)."</content>";
		$this->xml .= ($this->active) ? "<active>true</active>" : "<active>false</active>";
		$this->xml .= "</lesson>";
	}

	########################################################
	#### Database interface functions ######################
	########################################################

	# Save lesson (creates one if no id is set)
	public function save()
	{
		if (!empty($this->sectionId) && !empty($this->name) && !empty($this->content)) 
		{
			# Update existing lesson
			if (!empty($this->id))
			{
                $uri= trim($this->path, "/");
                $uriArr = explode("/", $uri);
                $query = sprintf("UPDATE lesson SET section_id = '%s', name = '%s', content = '%s' WHERE name = '%s' AND section_id = (SELECT id FROM section WHERE name = '%s' AND course_id = (SELECT id FROM course WHERE name = '%s' AND subject_id = (SELECT id FROM subject WHERE name = '%s' AND field_id = (SELECT id FROM field WHERE name = '%s'))))", 
                        pg_escape_string($this->sectionId),
                        pg_escape_string($this->name),
                        pg_escape_string($this->content),
                        pg_escape_string($uriArr[LESSON_INDEX]),
                        pg_escape_string($uriArr[SECTION_INDEX]),
                        pg_escape_string($uriArr[COURSE_INDEX]),
                        pg_escape_string($uriArr[SUBJECT_INDEX]),
                        pg_escape_string($uriArr[FIELD_INDEX]));
                $this->query = $query;
			}
			# New lesson
			else
			{
                $query = sprintf("INSERT INTO lesson (section_id, name, description, content) VALUES ('%s', '%s','%s', '%s')", pg_escape_string($this->sectionId), pg_escape_string($this->name), pg_escape_string($this->description), pg_escape_string($this->content));
                $this->query = $query;
			}

			# Run query
			$result = $this->db->exec($this->query);
	
			# Success
			if ($result)
			{
				return true;
			}
			else
			{
				array_push($GLOBALS['ERROR'],"Query ".$this->query." failed in Lesson:save()");
			}
		}

		# Failure
		return false;
	} 

	# Removes lesson from database
	public function delete()
	{
		# Delete query
		$query = sprintf("DELETE FROM lesson WHERE id = %s", pg_escape_string($this->id));
		$result = $this->db->exec($query);

		# Success
		if ($result)
		{
			return true;
		}

		# Failure
		return false;
	}

	# Marks lesson inactive
	public function disable()
	{
		$this->active = false;
		$this->save();
	}

	# Save's ilo's to DB
	public function saveIlos()
	{
		foreach ($this->ilos as $ilo)
		{
			$ilo->save();
		}
	}

	########################################################
	#### Functions for working with ILO's ##################
	########################################################

	# Load's array of ILO's from DB or from content
	public function loadIlos()
	{
		unset($this->ilos);
		$pattern = '/(<(span|div|p|img)[^>]*class="ilo" data-ilotype="([a-zA-z]+)" id="ilo([0-9]+)"[^>]*>[^<]*<\/\\2>)/';
		$ilocount = preg_match_all($pattern, $this->content, $iloArray);
		//return $iloArray;
		foreach ($iloArray[0] as $ndx => $content)
		{
			$id = $iloArray[4][$ndx];
			$type = $iloArray[3][$ndx];
			$this->ilos[$id] = new Ilo($id, null, null);
		}
		//return $this->ilos;
		if (!empty($this->ilos))
		{
			return true;
		}
		return false;
	}

	# Replaces ILO code with place holders in the objects content variable for storage
	public function removeILOCode()
	{
		$pattern = '/(<(span|div|p|img) id="([0-9]+)" data-ilotype="([a-zA-z]+)"[^>]*>[^<]*<\/\\2>)/';
		$replacement = '<ilo id="$2" />';
		$iloCount = preg_replace($pattern, $replacement, $this->content);
		$this->ilosIntact = false;
	}

	# Insert's ILO code into object's content variable for display
	public function insertILOCode()
	{
		foreach ($this->ilos as $id => $ilo)
		{
			$pattern = '/(<ilo id="'.$id.'" \/>)/';
			$replacement = $ilo->getContent();
			$this->content = preg_replace($pattern, $replacement, $this->content);
			$this->ilosIntact = true;
		}
	}

	########################################################
	### Getters and Setters ################################
	########################################################

	# Set content 
	public function setContent($content)
	{
		$this->content = $content;
	}

	# Set course
	public function setSection($sectionId)
	{
		$this->sectionId = $sectionId;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function setILOs($ilos)
	{
		# Kill old ilos
		unset($this->ilos);

		# Setup pattern for type extraction
		foreach ($ilos as $id => $ilo)
		{
			//$ilo = html_entity_decode($ilo);
			$ilo = "<parent>".$ilo."</parent>";
			try
			{
				$iloObj = new SimpleXMLElement($ilo);
				$oldIloId = (isset($iloObj->ilo->attributes()->oldiloid)) ? substr((string)$iloObj->ilo->attributes()->oldiloid, 3) : 0;
				$type= (string)$iloObj->ilo->attributes()->ilotype;
				$id = substr($id, 3);
				$content = (string)$iloObj->ilo->asXML();
				$this->ilos[$id] = new Ilo($id, $content, $type, $oldIloId);
			}
			catch (Exception $e)
			{
				// Hanlde errors
				return false;
			}
		}
		return true;
	}	

	public function setPath($path)
	{
		$this->path = $path;
	}
        
	public function getName()
	{
		return $this->name;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function getSection()
	{
		return $this->sectionId;
	}

	public function getILOs()
	{
		return $this->ilos;
	}

	public function getXML()
	{
		return $this->xml;
	}
        
	public function getPath()
	{
		return $this->path;
	}
}

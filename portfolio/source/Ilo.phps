<?php

Class Ilo
{
	########################################################
    #### Member Variables ##################################
    ########################################################

	private $db = null;
	private $id = null;
	private $oldId = null;
	private $content = null;
	private $type = null;
	private $typeName = null;
	private $typeId = null;

	########################################################
    #### Constructor ################# #####################
    ########################################################
	public function __construct($id = null, $content = null, $typeName = null, $oldIloId = null)
	{
		# Get DB Handle
		$this->db = $GLOBALS['db'];

		# Set ID	
		if (!empty($id))
		{	
			# Set ID
			$this->id = $id;

			# Load from ID
			$this->loadById($this->id);
		}

		# Set old ID if present
		if (!empty($oldIloId))
		{
			$this->oldId = $oldIloId;
		}
		else
		{
			$this->oldId = 0;
		}
		
		# Set content 
		if (!empty($content))
		{
			$this->content = $content;
		}

		# Set typename
		if (!empty($typeName))
		{
			$this->typeName = $typeName;
		}
	}

	########################################################
	#### Helper functions for loading ######################
	########################################################
	
	public function loadById($id)
	{
		$this->id = $id;
		$query = sprintf("SELECT * FROM ilo WHERE id = %s", pg_escape_string($this->id));
		$result = $this->db->query($query);
		if ($result)
		{
			$row = $result->fetch(PDO::FETCH_ASSOC);
			$this->typeId = $row['ilo_type_id'];
			$this->content = $row['content'];
			if(!empty($this->typeId))
            {
                $this->setTypeNameById($this->typeId);
            }
			return true;
		}
		return false;
	}

	########################################################
	#### Database interface functions ######################
	########################################################

	# Saves ILO to DB (always creates a new one, moving old to history table if necessary)
	public function save()
	{
		# Sanity Check
		if (!empty($this->id) && !empty($this->typeName) && !empty($this->content))
		{
			# Set Type ID if we only have name
			if (empty($this->typeId))
			{
				$this->setTypeIdByName($this->typeName);
			}
            
            $query = sprintf("SELECT count(*) AS count FROM ilo WHERE id = %s",pg_escape_string($this->id));
            $result = $this->db->query($query);
            
            if($result)
            {
                $row = $result->fetch(PDO::FETCH_ASSOC);
                
                # If this is a new ILO or an edited ILO, submit it to the database
				if($row['count'] == 0)
                {
                    $ILOXML = new SimpleXMLElement($this->content);
                    $oldiloid = null;
                    
                    foreach($ILOXML->attributes() as $name => $value)
                    {
                        if($name=="oldiloid")
                        {
                            $oldiloid=$value;
                            $this->content = preg_replace('/ oldiloid=[\'\"]ilo[0-9]+?[\'\"]/',"",$this->content,1);
                        }
                    }
                    
                    $query = sprintf("INSERT INTO ilo (id,ilo_type_id,content) VALUES (%s,%s,'%s')",
                                      pg_escape_string($this->id),
                                      pg_escape_string($this->typeId),
                                      pg_escape_string($this->content));

                    $result = $this->db->query($query);

                    if(!$result)
                    {
                        array_push($GLOBALS['ERROR'],"Error in inserting ILO Ilo:save() with query".$query);
                        return false; 
                    }

                    if(!is_null($oldiloid))
                    {
                        $query = sprintf("INSERT INTO ilo_history (new_ilo_id, old_ilo_id, ilo_type_id, content) VALUES (%s, %s, (SELECT ilo_type_id FROM ilo WHERE id = %s), (SELECT content FROM ilo WHERE id = %s))",
                                        pg_escape_string($this->id),
                                        pg_escape_string($value),
                                        pg_escape_string($value),
                                        pg_escape_string($value));
                        $result = $this->db->query($query);

                        if(!$result)
                        {
                            array_push($GLOBALS['ERROR'],"Error in inserting old ILO in Ilo:save() with query".$query);
                            return false; 
                        }

                        $query = sprintf("DELETE FROM ilo WHERE id = %s;",pg_escape_string($value));

                        if(!$result)
                        {
                            array_push($GLOBALS['ERROR'],"Error in deleting old ILO in Ilo:save() with query".$query);
                            return false; 
                        }
                    }
                    
                    return true;
                }
                # If this ILO is new and unedited, then we just return true
                else
                {
                    return true;
                }
            }
            
            return false;
            
			/*
			# Determine if ILO exists
			$query = sprintf("SELECT count(*) AS count FROM ilo WHERE id = %s", pg_escape_string($this->id));
			$result = $this->db->query($query);
			if ($result)
			{
				$row = $result->fetch(PDO::FETCH_ASSOC);
				if ($row['count'] == 1)
				{
					$query = sprintf("UPDATE ilo SET ilo_type_id = %s, content = '%s' WHERE id = %s", pg_escape_string($this->typeId), pg_escape_string($this->content), pg_escape_string($this->id));
				}
				else
				{
					$query = sprintf("INSERT INTO ilo (id, ilo_type_id, content) VALUES (%s, %s, '%s')", pg_escape_string($this->id), pg_escape_string($this->typeId), pg_escape_string($this->content));
				}
	
				$saveResult = $this->db->exec($query);
				if ($saveResult)
				{
					return true;
				}
			}*/
		}
        if(empty($this->id))
        {
            array_push($GLOBALS['ERROR'],"ILO ID Empty");
        }
        
        if(empty($this->typeName))
        {
            array_push($GLOBALS['ERROR'],"ILO typeName Empty");
        }
        
        if(empty($this->content))
        {
            array_push($GLOBALS['ERROR'],"ILO Content Empty");
        }
        
        array_push($GLOBALS['ERROR'],"Error: ILO data invalid in Ilo:save()".empty($this->id).empty($this->typeName).empty($this->content));
		return false;
	}

	public function delete()
	{
		if (!empty($this->id) && is_int($this->id))
		{
			$query = sprintf("DELETE FROM ilo WHERE id = %s", pg_escape_string($this->id));
			$result = $this->db->exec($query);
			if ($result)
			{
				return true;
			}
		}
		return false;
	}

	########################################################
	#### Getters and Setters ######## ######################
	########################################################

	# Get ID
	public function getId()
	{
		return $this->id;
	}

	# Get Type Id
	public function getTypeId()
	{
		return $this->typeId;	
	}

	# Get Type Name
	public function getTypeName()
	{
		return $this->type;
	}

	# Get Content
	public function getContent()
	{
		return $this->content;
		//$contentObj = new SimpleXMLElement($this->content);
		//$contentString = (string)$contentObj->ilo->asXML();
		//return $contentString;	
	}

	# Set Content
	public function setContent($content)
	{
		$this->content = $content;
	}

	# Set type id and type name from type name
	public function setTypeIdByName($typeName)
	{
		$this->typeName = $typeName;
		$query = sprintf("SELECT id FROM ilo_type WHERE name = '%s'", pg_escape_string(strtolower($this->typeName)));
		$result = $this->db->query($query);
		if ($result)
		{
			$row = $result->fetch(PDO::FETCH_ASSOC);
			$this->typeId = $row['id'];
			return true;
		}
        
        array_push($GLOBALS['ERROR'],"Error in Ilo:setTypeIdByName() with query ".$query);
		return false;
	}

	# Set type id and type name from type id
	public function setTypeNameById($typeId)
	{
		$this->typeId = $typeId;
		$query = sprintf("SELECT name FROM ilo_type WHERE id = %s", pg_escape_string(strtolower($this->typeId)));
		$result = $this->db->query($query);
		if ($result)
		{
			$row = $result->fetch(PDO::FETCH_ASSOC);
			$this->typeName = $row['name'];
            return true;
		}
        
        array_push($GLOBALS['ERROR'],"Error in Ilo:setTypeNameById() with query ".$query);
		return false;
	}
}

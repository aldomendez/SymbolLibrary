<?php
ini_set('display_errors','off');
ini_set('date.timezone', 'America/Mexico_City');
error_reporting(E_ALL ^ E_NOTICE);
	/* * * * * * * * * * * * * * * * * *
	 *
	 * <- Backend for Cenny.js ->
	 * 
	 * How data is stored:
	 *   Backend (root) directory
	 *      v
	 *    Group directory
	 *      v
	 *     User directory
	 *      v
	 *      Data.txt
	 *
	 *
	/* * * * * * * * * * * * * * * * * */
	
	function openFile($url, $size) {
		if (file_exists($url)) {
		$myFile = $url;
		$fh = fopen($myFile, 'r');
		$dataToReturn = fread($fh, $size);
		fclose($fh);	
		} else {
			saveFile($url, "");
			$dataToReturn = "";
		}
		return $dataToReturn;
	
	
	}
	
	function delete_files($dir) {
	  if (is_dir($dir)) {
	    $objects = scandir($dir);
	    foreach ($objects as $object) {
	      if ($object != "." && $object != "..") {
	        if (filetype($dir."/".$object) == "dir") 
	           rrmdir($dir."/".$object); 
	        else unlink   ($dir."/".$object);
	      }
	    }
	    reset($objects);
	    rmdir($dir);
	  }
	 }
	 
	function splitString($stringX,$by) {
	  $arrayX = explode($by, $stringX);
	  return $arrayX;
	}

	function saveFile($url, $dataToSave) {
		$myFile = $url;
		$fh = fopen($myFile, 'w') or die($myFile);
		fwrite($fh, $dataToSave);
		fclose($fh);
	
	
	}
	
	function addToFile($url, $dataToSave) {
		$myFile = $url;
		$fh = fopen($myFile, 'a') or die($myFile);
		fwrite($fh, $dataToSave);
		fclose($fh);
	
	
	}
	
	//***********************************************************
		$group_loggedin = false;
		$user_loggedin = false;
	//***********************************************************

	$groupName = $_POST['groupName'];
	$groupKey = $_POST['groupKey'];
	
	$userName = $_POST['userName'];
	$userPass = $_POST['userPass'];
    
    
    
	$directory = "backend";
	if (file_exists("backend/")) {} else {
		mkdir("backend/", 0700);
		
	}
	
	//#######################################################################################################################################################
	//############################################################### GROUP / USER ##########################################################################
	//#######################################################################################################################################################
	
	// groups
	
	if (file_exists("$directory/$groupName/")) {
		$orginalKey = openFile("$directory/$groupName/key.txt", 10000);
		if ($orginalKey === $groupKey) {
			$group_loggedin = true;
		} else {
			echo '{"error":"group key incorrect"}';
		}
		
	} else {
		mkdir("$directory/$groupName/", 0700);
		saveFile("$directory/$groupName/key.txt", $groupKey);
			$group_loggedin = true;
	}
	
	// users
	
	if (file_exists("$directory/$groupName/$userName/")) {
		$orginalPass = openFile("$directory/$groupName/$userName/pass.txt", 10000);
		if ($orginalPass === $userPass) {
			$user_loggedin = true;
		} else {
			echo '{"error":"user pass incorrect"}';
		}
		
	} else {
		mkdir("$directory/$groupName/$userName/", 0700);
		saveFile("$directory/$groupName/$userName/pass.txt", $userPass);
        addToFile("$directory/$groupName/userlist.txt", $userName . "@SEPCENNYUSER@");
			$user_loggedin = true;
	}
	
	
	
	//########################################################################################################################
	if ($group_loggedin === true && $user_loggedin === true) {

    
    //**********************************************
    //get actions / data from request
    $action = $_POST['action'];
	$clientData = $_POST['data'];
    //formatting
	$clientData = str_replace("\'","'",$clientData);
	$clientData = str_replace('\"','"',$clientData);
    //**********************************************
    
		
		// - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - /
		if ($action === "get") {
			
			$openedData = openFile("$directory/$groupName/$userName/data.txt", 500000);
			$getProperties = $_POST['getProperties'];
			if ($getProperties === "all") {
			
				if ($openedData !== "") {
				    echo $openedData;
            	} else {
            	    echo '{"error":"user is empty"}';
            	}
            
            } else {
            	$getProperties = splitString($getProperties, "@n@");	
            	if ($openedData !== "") {
            	$openedData = json_decode($openedData);
            	$outputObj = array();
            
            	foreach ($openedData as $pName => $pData) {
            		for ($x=0; $x< count($getProperties); $x++) {
  						if ($pName === $getProperties[$x]) {
  							$outputObj[$pName] = $pData;
  						}
  					}
  				}
  				
  				$outputObj = json_encode($outputObj); //encode output obj to json
           		echo $outputObj;
  				
  				} else {//opened data is empty
  					echo '{"error":"user is empty"}';
  				}
  				
  				
            }
            
		} else if ($action === "getOther") {
			
			$otherUser = $_POST['otherUser'];
	    
	        if (file_exists("$directory/$groupName/$otherUser/")) {
	        	$openedRead = openFile("$directory/$groupName/$otherUser/read.txt", 1000);
	        	$arrayX = splitString($openedRead,"@n@");
	        	$userFoundInP = false;
	        	for ($x=0; $x< count($arrayX); $x++) {//look for current user in $otherUser's perms
  					if ($arrayX[$x] === $userName) {
  						$userFoundInP = true;
  					}
  				} 
	        	if ($userFoundInP === true || $openedRead === "allowAll") {
	        		$openedData = openFile("$directory/$groupName/$otherUser/data.txt", 500000);
					if ($openedData !== "") {
			 			echo $openedData;
             	 	} else {
                 		echo '{"error":"user is empty."}';
              	 	}
           		 } else { //check if any property allows $userName read access
           		 	$openedPropertyPerm = openFile("$directory/$groupName/$otherUser/propertyPerm.txt", 50000);
           		 	$openedData = openFile("$directory/$groupName/$otherUser/data.txt", 500000); //actual data
           		 	$openedData = json_decode($openedData);
           		 	$permissionProperties = json_decode($openedPropertyPerm);
           		 	
           		 	$isThere = false;
           		 	$outputObj = array(); //create obj for properties user is given access to.
           		 	
           		 	foreach ($permissionProperties as $propertyName => $propertyValue) {
           		 		$propertyUsers = splitString($propertyValue,"@n@");
           		 		for ($x=0; $x< count($propertyUsers); $x++) {//look for current user in $otherUser's perms
  							if ($propertyUsers[$x] === $userName) {
  								
  								$isThere = true;
  								foreach ($openedData as $dataPropertyName => $dataPropertyData) {
  									if ($propertyName === $dataPropertyName) {
  										$outputObj[$propertyName] = $dataPropertyData;
  									}
  								}
  								
  							} else if ($propertyValue === "allowAll") {
  								$isThere = true;
								foreach ($openedData as $dataPropertyName => $dataPropertyData) {
  									if ($propertyName === $dataPropertyName) {
  										$outputObj[$propertyName] = $dataPropertyData;
  									}
  								}
  							}
  						} 
           		 
           		 
           		 	}
           		 	
           		 	if ($isThere === true) { //if $userName is found in properties do this
           		 		$outputObj = json_encode($outputObj); //encode output obj to json
           		 		echo $outputObj;
           		 	} else { //otherwise, present error
           		 		echo '{"error":"access not granted"}';	
           		 	}
           		 
           		 }
        	} else {
            	echo '{"error":"user does not exist."}';
        	}
		
		} else if ($action === "getEmailOther") {
			
			$otherUser = $_POST['otherUser'];
			
	        if (file_exists("$directory/$groupName/$otherUser/")) {
	        	$openedRead = openFile("$directory/$groupName/$otherUser/emailRead.txt", 1000);
	        	$arrayX = splitString($openedRead,"@n@");
	        	$userFoundInP = false;
	        	for ($x=0; $x< count($arrayX); $x++) {//look for current user in $otherUser's perms
  					if ($arrayX[$x] === $userName) {
  						$userFoundInP = true;
  					}
  				} 
	        	if ($userFoundInP === true || $openedRead === "allowAll" || $otherUser === $userName || $openedRead === "") {
	        		$openedEmail = openFile("$directory/$groupName/$otherUser/email.txt", 1000);
					if ($openedEmail !== "") {
			 			echo json_encode($openedEmail);
             	 	} else {
                 		echo '{"error":"email not set."}';
              	 	}
           		 } else {
           			echo '{"error":"email access not granted."}';
           		 }
        	} else {
            	echo '{"error":"user "' + $otherUser + '" does not exist."}';
        	}
		
		} else if ($action === "setOther") {
			
			$otherUser = $_POST['otherUser'];
	    
	        if (file_exists("$directory/$groupName/$otherUser/")) {
	        	$openedWrite = openFile("$directory/$groupName/$otherUser/write.txt", 1000);
	        	$arrayX = splitString($openedWrite,"@n@");
	        	$userFoundInP = false;
	        	for ($x=0; $x< count($arrayX); $x++) {//look for current user in $otherUser's perms
  					if ($arrayX[$x] === $userName) {
  						$userFoundInP = true;
  					}
  				} 
	        	if ($userFoundInP === true || $openedWrite === "allowAll") {
	        		saveFile("$directory/$groupName/$otherUser/data.txt", $clientData);
	        		echo json_encode("saved to user " . $otherUser . " in group " . $groupName);
           		 } else {
           			echo '{"error":"write access not granted."}';
           		 }
        	} else {
            	echo '{"error":"user does not exist."}';
        	}
		
		} else if ($action === "set") {
		
			saveFile("$directory/$groupName/$userName/data.txt", $clientData);
			echo json_encode("saved to user " . $userName . " in group " . $groupName);
			
		} else if ($action === "permissions") {
			if ($userName !== "default") { //make sure no permissions are set on default user
				$read = $_POST['read'];
				$write = $_POST['write'];
				$emailRead = $_POST['emailRead'];
				$propertyObj = $_POST['propertyObj']; //for specific properties
				if ($read !== "DoNotEdit") {
				saveFile("$directory/$groupName/$userName/read.txt", $read);
				}
				if ($write !== "DoNotEdit") {
				saveFile("$directory/$groupName/$userName/write.txt", $write);
				}
				if ($emailRead !== "DoNotEdit") {
				saveFile("$directory/$groupName/$userName/emailRead.txt", $emailRead);
				}
				if ($propertyObj !== "DoNotEdit") {//updates permissions on properties
					//clean up property obj
					$propertyObj = str_replace("\'","'",$propertyObj);
					$propertyObj = str_replace('\"','"',$propertyObj);
					
					
					$unpackedPropertyObj = json_decode($propertyObj);
					
					$openedPermProperties = openFile("$directory/$groupName/$userName/propertyPerm.txt",50000);
					$openedPermProperties = json_decode($openedPermProperties);
					
					$outputPermProperties = array();
					
					foreach ($unpackedPropertyObj as $pName => $pData) {
					foreach ($openedPermProperties as $ppName => $ppData) {
  						if ($pName === $ppName) {
  							$outputPermProperties[$ppName] = $pData;
  						} else {
  							$outputPermProperties[$pName] = $pData;
  						}
  						$outputPermProperties[$ppName] = $ppData;
  					}
  					}

					saveFile("$directory/$groupName/$userName/propertyPerm.txt", json_encode($outputPermProperties));
				}
				echo json_encode("Permissions updated");
			} else {
				echo json_encode("Permissions cannot be edited on default user");
			}
			
		
		} else if ($action === "removeUser") {
		
			//remove user directory
			delete_files("$directory/$groupName/$userName/");
			
			//remove from user list
			$users = openFile("$directory/$groupName/userlist.txt", 500000);
			$users = str_replace("$userName@SEPCENNYUSER@", "", $users);
			saveFile("$directory/$groupName/userlist.txt", $users);
			
			echo json_encode("removed user " . $userName . " in group " . $groupName);
			
		} else if ($action === "removeGroup") {
		
			delete_files("$directory/$groupName/");
			echo json_encode("removed group " . $groupName . " and all users within.");
			
		} else if ($action === "setEmail") {
            
            saveFile("$directory/$groupName/$userName/email.txt", $clientData);
                  
        } else if ($action === "listUsers") {
            $users = openFile("$directory/$groupName/userlist.txt", 500000);
            echo json_encode($users);
        } else {
            echo json_encode("finished");
        }
		// - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - / - /
	
	
	}//close authentication if statement
	//########################################################################################################################
	
	
	
	


?>
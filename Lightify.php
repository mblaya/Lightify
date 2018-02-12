<?php
/*
  Plugin Name: Lightify
  Plugin URI: http://blaya.club
  Description: Lightify
  Author: Mariano Blaya
  Author URI: http://blaya.club
  License: GPLv2+
  Text Domain: Lightify
*/

function Lightify_authorize( $username, $password, $serialNumber )
{

	$url = 'https://eu.lightify-api.org/lightify/services/session';
	$content = array(
		'username' => $username,
		'password' => $password,
		'serialNumber' => $serialNumber
		);

	$curl = curl_init( $url );
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($content) );

	$json_response = curl_exec($curl);

	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ( $status != 200 ) {
    		die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	}

	curl_close($curl);
	$response = json_decode($json_response, true);
	$security_token = $response['securityToken'];

	return $security_token;
}


/*
*********************************
*/
function Lightify_getDevices( $securityToken )
{
	$url = 'https://eu.lightify-api.org/lightify/services/devices';

	$curl = curl_init( $url );
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_VERBOSE, true);	
	$verbose = fopen('smarthome.log', 'w+');
	curl_setopt($curl, CURLOPT_STDERR, $verbose);	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
		array( 'Content-type: application/json',
			'authorization: ' . $securityToken ) );
	
	$json_response = curl_exec($curl);

	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ( $status != 200 ) {
    		die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	}

	curl_close($curl);
	$response = json_decode($json_response, true);
	return $response;
}

/*
*********************************
*/
function Lightify_setDevice( $securityToken, $deviceID, $newStatus )
{
	if( !is_numeric( $deviceID ) )
		$deviceID = Lightify_getNumericDeviceID( $securityToken, $deviceID );
	
	$url = 'https://eu.lightify-api.org/lightify/services/device/';
	$url .= 'set?idx=' . $deviceID . '&' . 'onoff=' . $newStatus;

	$curl = curl_init( $url );
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_VERBOSE, true);	
	$verbose = fopen('smarthome.log', 'w+');
	curl_setopt($curl, CURLOPT_STDERR, $verbose);	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
		array( 'Content-type: application/json',
			'authorization: ' . $securityToken ) );
	
	$json_response = curl_exec($curl);

	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ( $status != 200 ) {
    		die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	}

	curl_close($curl);
	$response = json_decode($json_response, true);
	return $response;
}

/*
*********************************
*/
function Lightify_showDevices( $devices, $groups, $whole_list )
{

	print( '<table style="width:100%">' );
	print( '<th>ID</th> <th>Name</th> <th>Model</th> <th>Online</th> <th>On/Off</th> <th>Groups</td>' );
	foreach( $devices as $device )
	{
	   print( '<tr>' );
	   printf( "<td>%s</td> <td>%s</td> <td>%s</td> <td>%s</td> <td>%s</td>",
	          $device['deviceId'], $device['name'], $device['modelName'],
        	  $device['online'] == "1" ? "Online" : "Offline",
        	  $device['on'] == "0" ? "OFF" : "ON"
   		);
   	  print( "<td>" );
   	  foreach( $device['groupList'] as $group )
   	  {
   	  	printf( "%s, ", $groups[$group-1]['name'] );
   	  }
   	  print( "</td>" );
 	  print( '</tr>' );
	}
	print( '</table>' );
	if( $whole_list == 1 )
	{
		print( "Begin DEVICES LIST<br>" );
		print_r( $devices );
		print( "End DEVICES LIST<br>" );
		print( "Begin Groups LIST<br>" );
		print_r( $groups );
		print( "End Groups LIST<br>" );
	}
}

/*
*********************************
*/
function Lightify_getNumericDeviceID( $securityToken, $deviceID )
{
	$devices = Lightify_getDevices( $securityToken );
	foreach( $devices as $device )
	{
		print( "Device name = " . $device['name'] . "<br>" );
		if( $device['name'] == $deviceID )
		{
			printf( "Found = %s - %d\n", $deviceID, $counter );
			return $device['deviceId'];
		}
	}	
}

/*
*********************************
*/
function Lightify_getNumericGroupID( $securityToken, $groupID )
{
	$groups = Lightify_getGroups( $securityToken );
	foreach( $groups as $group )
	{
		print( "Group name = " .$group['name'] . "<br>" );
		if( $group['name'] == $groupID )
		{
			printf( "Found = %s - %d\n", $groupID, $counter );
			return $group['groupId'];
		}
	}	
}

/*
*********************************
*/
function Lightify_getNumericSceneID( $securityToken, $sceneID )
{
	$groups = Lightify_getGroups( $securityToken );
	foreach( $groups as $group )
	{
		$counter = 0;
		foreach( $group['scenes'] as $scene )
		{
			$counter ++;
			printf( "Scene = %s\n", $scene );
			if( $scene == $sceneID )
			{
				printf( "Found = %s - %d\n", $sceneID, $counter );
				$sceneID = $counter;
				return $counter;
			}
		}
	}	
}

/*
*********************************
*/
function Lightify_recallScene( $securityToken, $sceneID, $groupID )
{
	if( !is_numeric($sceneID) )
		$sceneID = Lightify_getNumericSceneID( $securityToken, $sceneID );
	
	$url = 'https://eu.lightify-api.org/lightify/services/scene/';
	$url .= 'recall?sceneId=' . $sceneID;
	
	$curl = curl_init( $url );
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_VERBOSE, true);	
	$verbose = fopen('smarthome.log', 'w+');
	curl_setopt($curl, CURLOPT_STDERR, $verbose);	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
		array( 'Content-type: application/json',
			'authorization: ' . $securityToken ) );
	
	$json_response = curl_exec($curl);

	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ( $status != 200 ) {
    		die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	}

	curl_close($curl);
	$response = json_decode($json_response, true);
	return $response;
} 

/*
*********************************
*/
function Lightify_setGroup( $securityToken, $groupID, $newStatus )
{
	if( !is_numeric( $groupID ) )
		$groupID = Lightify_getNumericGroupID( $securityToken, $groupID );
	
	$url = 'https://eu.lightify-api.org/lightify/services/group/';
	$url .= 'set?idx=' . $groupID . '&onoff=' . $newStatus;

	$curl = curl_init( $url );
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_VERBOSE, true);	
	$verbose = fopen('smarthome.log', 'w+');
	curl_setopt($curl, CURLOPT_STDERR, $verbose);	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
		array( 'Content-type: application/json',
			'authorization: ' . $securityToken ) );
	
	$json_response = curl_exec($curl);

	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ( $status != 200 ) {
    		die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	}

	curl_close($curl);
	$response = json_decode($json_response, true);
	return $response;
}

/*
*********************************
*/
function Lightify_getGroups( $securityToken )
{
	$url = 'https://eu.lightify-api.org/lightify/services/groups';

	$curl = curl_init( $url );
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_VERBOSE, true);	
	$verbose = fopen('smarthome.log', 'w+');
	curl_setopt($curl, CURLOPT_STDERR, $verbose);	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
		array( 'Content-type: application/json',
			'authorization: ' . $securityToken ) );
	
	$json_response = curl_exec($curl);

	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ( $status != 200 ) {
    		die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	}

	curl_close($curl);
	$response = json_decode($json_response, true);
	return $response;
}


/*
*********************************
*/
function Lightify_showGroups( $groups, $devices, $whole_list )
{

	print( '<table style="width:100%">' );
	print( '<th>ID</th> <th>Name</th> <th>Devices</th> <th>Scenes</th>' );
	foreach( $groups as $group )
	{
	   print( '<tr>' );
	   printf( "<td>%s</td> <td>%s</td>", $group['groupId'], $group['name'] );
	   print( "<td>" );
   	  foreach( $group['devices'] as $device)
   	  {
   	  	printf( "%s, ", $devices[$device-1]['name'] );
   	  }
   	  print( "</td>" );
 	  
 	  print( "<td>" );
   	  foreach( $group['scenes'] as $scene )
   	  {
   	  	printf( "%s, ", $scene);
   	  }
   	  print( '</td>' );
 	  print( '</tr>' );
	}
	print( '</table>' );
	if( $whole_list == 1 )
	{
		print( "Begin DEVICES LIST<br>" );
		print_r( $devices );
		print( "End DEVICES LIST<br>" );
		print( "Begin Groups LIST<br>" );
		print_r( $groups );
		print( "End Groups LIST<br>" );
	}
}

?>
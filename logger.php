<?php
$pp_json_text = file_get_contents("data.json");
$pp_json = json_decode($pp_json_text, true);

$mysqli = new mysqli("hostname", "username", "password", "db_name");

$stmt = null;

if(!isset($_GET['path']) || strlen($_GET['path']) == 0)
    die("No path");

// The sanity checks are to avoid someone poisoning
// the logging data. I think checking the existence
// of the link in the file is strong enough.
//
// Of course, someone could just spam an existing link
// thus making the logging worthless, but I can't be
// bothered to write code to protect against that.

// Check to make sure the link exists in our data file
function sanityCheckExternal($link){
    global $pp_json_text;
    return(strpos($link, 'http://') !== FALSE && strpos($pp_json_text, $link) !== FALSE);
}

// Check to make sure the internal node exists in our data file
function sanityCheckInternal($path){
    global $pp_json;
    $nodes = explode('/', $path);
    $currentNode = $pp_json;
    foreach($nodes as $node){
        if(isset($currentNode['children'][$node]))
            $currentNode = $currentNode['children'][$node];
        else
            return false;
    }
    return true;
}

if($_GET['path'][0] == '#'){
    if(!sanityCheckInternal(substr($_GET['path'], 1)))
        die("Sanity check failed");
    $stmt = $mysqli->prepare("INSERT INTO internal_hits(path) VALUES (?)");
}else{
    if(!sanityCheckExternal($_GET['path']))
        die("Sanity check failed");
    $stmt = $mysqli->prepare("INSERT INTO external_hits(path) VALUES (?)");
}

$stmt->bind_param("s", $_GET['path']);

$stmt->execute();
$stmt->close();
?>
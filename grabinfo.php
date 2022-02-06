<?php 
	
//DB CONNECTION
if($_POST){
	$gender = $_POST['gender'];
	$emotion = $_POST['emotion'];
	$pet = $_POST['pet'];
	$accessories = $_POST['accessories'];
}
	
	$servername = "localhost";
	$username = "secondcl_drawing";
	$password = "t!klm90#A21!";
	$dbname = "secondcl_ushoulddraw";
	
	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	} 
	
	$baseclasssql = "SELECT name FROM drawoptions where type = 'Base Class' ORDER BY RAND() LIMIT 1;";
	$baseclass = $conn->query($baseclasssql);
	while ($row = mysqli_fetch_assoc($baseclass)){
		$baseclass_output = $row["name"];
	}
	
	$majorfeaturesql = "SELECT name FROM drawoptions where type = 'Major Feature' ORDER BY RAND() LIMIT 1;";
	$majorfeature = $conn->query($majorfeaturesql);
	while ($row = mysqli_fetch_assoc($majorfeature)){
		$majorfeature_output = $row["name"];
	}
	
	if (!$accessories){
		$accessories = 1;
	}
	
	$i = 0;
	
	$accessorysql = "SELECT name FROM drawoptions where type = 'Accessories' ORDER BY RAND() LIMIT $accessories;";
	$accessory = $conn->query($accessorysql);
	while ($row = mysqli_fetch_assoc($accessory)){
		$accessory_output[$i] = $row["name"];
		$i++;
	}
	
	if (isset($emotion)){
		$emotionsql = "SELECT name FROM drawoptions where type = 'Emotion' ORDER BY RAND() LIMIT 1;";
		$emotion = $conn->query($emotionsql);
		while ($row = mysqli_fetch_assoc($emotion)){
			$emotion_output = $row["name"];
		}
	}
	
	if (isset($pet)){
		$petsql = "SELECT name FROM drawoptions where type = 'Pet' ORDER BY RAND() LIMIT 1;";
		$pet = $conn->query($petsql);
		while ($row = mysqli_fetch_assoc($pet)){
			$pet_output = $row["name"];
		}
	}	

$vowels = array('A', 'E', 'I', 'O', 'U');  	
	
function weighted_random_simple($values, $weights){ 
    $count = count($values); 
    $i = 0; 
    $n = 0; 
    $num = mt_rand(0, array_sum($weights)); 
    while($i < $count){
        $n += $weights[$i]; 
        if($n >= $num){
            break; 
        }
        $i++; 
    } 
    return $values[$i]; 
}
?> 
You should draw 
<?php 

if (isset($emotion)){
		if (in_array($emotion_output[0], $vowels)){ 
			echo 'an '; 
		} else { 
			echo 'a '; 
		} 
	echo $emotion_output ." ";
}

if (!isset($emotion)){
	if (in_array($majorfeature_output[0], $vowels)){ 
		echo 'an '; 
	} else { 
		echo 'a '; 
	}
}
echo $majorfeature_output ." "; 

if (isset($gender)){
	$values = array("Male", "Female", "Androgynous");
	$weights = array(49, 47, 2);
	$gender_output = weighted_random_simple($values, $weights);

echo $gender_output ." ";	
}

echo $baseclass_output; 

?> with <?php 
if (substr($accessory_output[0], -1) != "s"){ 
	$accessory1 = substr($accessory_output[0], 0, 1);
	if (in_array($accessory1, $vowels)){ 
		echo 'an '; 
	} else { 
		echo 'a '; 
	}
} 
echo $accessory_output[0]; 

if ($accessories > 1){ 
 
	if ($accessories == 2){
		echo " and ";
	} else {
		echo ", ";
	}
	
	if (substr($accessory_output[1], -1) != "s") {
		$accessory2 = substr($accessory_output[1], 0, 1);
		if (in_array($accessory2, $vowels)){ 
			echo 'an '; 
		} else { 
			echo 'a '; 
		}} 
	echo $accessory_output[1]; 
}; 

if ($accessories > 2){
	echo ", and ";
		if (substr($accessory_output[2], -1) != "s") {
		$accessory3 = substr($accessory_output[2], 0, 1);
		if (in_array($accessory3, $vowels)){ 
			echo 'an '; 
		} else { 
			echo 'a '; 
		}} 
	echo $accessory_output[2];
}

if (isset($pet)){
	echo " that owns ";
		if (substr($pet_output, -1) != "s") {
			if (in_array($pet_output[0], $vowels)){ 
				echo 'an '; 
			} else { 
				echo 'a '; 
			}
		}
	echo $pet_output;
}
?>
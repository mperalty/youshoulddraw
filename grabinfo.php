<?php 
	
//DB CONNECTION
if($_POST){
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $emotion = filter_input(INPUT_POST, 'emotion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $pet = filter_input(INPUT_POST, 'pet', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $accessories = filter_input(INPUT_POST, 'accessories', FILTER_VALIDATE_INT);
        if ($accessories === false || $accessories === null) {
                echo "Invalid accessories value. Defaulting to 1.<br />";
                $accessories = 1;
        }
}
	
	require "includes/dbcon.php";
	
	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	} 
	
        $stmt = $conn->prepare("SELECT name FROM drawoptions WHERE type = ? ORDER BY RAND() LIMIT 1");
        $type = 'Base Class';
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $stmt->bind_result($baseclass_output);
        $stmt->fetch();
        $stmt->close();
	
        $stmt = $conn->prepare("SELECT name FROM drawoptions WHERE type = ? ORDER BY RAND() LIMIT 1");
        $type = 'Major Feature';
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $stmt->bind_result($majorfeature_output);
        $stmt->fetch();
        $stmt->close();
	
	if (!$accessories){
		$accessories = 1;
	}
	
	$i = 0;
	
        $stmt = $conn->prepare("SELECT name FROM drawoptions WHERE type = 'Accessories' ORDER BY RAND() LIMIT ?");
        $stmt->bind_param('i', $accessories);
        $stmt->execute();
        $stmt->bind_result($accessory_name);
        while ($stmt->fetch()) {
                $accessory_output[$i] = $accessory_name;
                $i++;
        }
        $stmt->close();
	
	if (isset($emotion)){
                $stmt = $conn->prepare("SELECT name FROM drawoptions WHERE type = ? ORDER BY RAND() LIMIT 1");
                $type = 'Emotion';
                $stmt->bind_param('s', $type);
                $stmt->execute();
                $stmt->bind_result($emotion_output);
                $stmt->fetch();
                $stmt->close();
	}
	
	if (isset($pet)){
                $stmt = $conn->prepare("SELECT name FROM drawoptions WHERE type = ? ORDER BY RAND() LIMIT 1");
                $type = 'Pet';
                $stmt->bind_param('s', $type);
                $stmt->execute();
                $stmt->bind_result($pet_output);
                $stmt->fetch();
                $stmt->close();
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
        echo htmlspecialchars($emotion_output) ." ";
}

if (!isset($emotion)){
	if (in_array($majorfeature_output[0], $vowels)){ 
		echo 'an '; 
	} else { 
		echo 'a '; 
	}
}
echo htmlspecialchars($majorfeature_output) ." ";

if (isset($gender)){
	$values = array("Male", "Female", "Androgynous");
	$weights = array(49, 47, 2);
	$gender_output = weighted_random_simple($values, $weights);

echo htmlspecialchars($gender_output) ." ";
}

echo htmlspecialchars($baseclass_output);

?> with <?php 
if (substr($accessory_output[0], -1) != "s"){ 
	$accessory1 = substr($accessory_output[0], 0, 1);
	if (in_array($accessory1, $vowels)){ 
		echo 'an '; 
	} else { 
		echo 'a '; 
	}
} 
echo htmlspecialchars($accessory_output[0]);

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
        echo htmlspecialchars($accessory_output[1]);
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
        echo htmlspecialchars($accessory_output[2]);
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
        echo htmlspecialchars($pet_output);
}
?>

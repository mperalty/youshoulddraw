<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>You Should Draw - Random Character Art Ideas Generator</title>
  <meta name="description" content="You Should Draw - Random Character Art Ideas Generator">
  <meta name="author" content="Malcolm Peralty">

  <link rel="stylesheet" href="style.css">
  <link rel='stylesheet' id='open-sans-css'  href='//fonts.googleapis.com/css?family=Open+Sans%3A300italic%2C400italic%2C600italic%2C300%2C400%2C600&#038;subset=latin%2Clatin-ext&#038;ver=4.5.2' type='text/css' media='all' />

  <!--[if lt IE 9]>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

<script src="jquery-3.6.0.min.js"></script>
<script type="text/javascript">
function toggleDiv(divId) {
   $("#"+divId).slideToggle( "slow" );
}
</script>

</head>
<body>
<script type="text/javascript">
$(document).ready(function() {
	
	$.ajax({
		type : 'POST',
		url : 'grabinfo.php',
		data : "firstload",
		success : function(data){
			$(".main").fadeIn(500).show(function(){
        			$(".main").html(data);
       			});
 			}
	});
	
	 $(document).on('submit', '#optionsform', function() {
		 var data = $(this).serialize();
		 $.ajax({
  
 		type : 'POST',
  		url  : 'grabinfo.php',
  		data : data,
  		success :  function(data){
       			$(".main").fadeIn(500).show(function(){
        			$(".main").html(data);
       			});
 			}
  		});
  		 	return false;
	});
});
</script>
<h3 class="main"></h3>

<div id="draw_options">
<form method="post" id="optionsform" action="<?php $_PHP_SELF ?>">
<div class="left_side"><input type="checkbox" name="gender" id="gender" value="Gender">Random Gender?<br />
<input type="checkbox" name="emotion" id="emotion" value="Emotion">Random Emotion?<br />
<input type="checkbox" name="pet" id="pet" value="Pet">Random Pet?</br>
</div>
<div class="right_side">
<input type="radio" name="accessories" class="accessories" value="1" checked>One Accessory<br />
<input type="radio" name="accessories" class="accessories" value="2">Two Accessories<br />
<input type="radio" name="accessories" class="accessories" value="3">Three Accessories<br />
</div>
</div>
<div class="subdraw_notice">
	<a href="javascript:toggleDiv('draw_options');" class="options button">Options</a>
	<input type="submit" class="submit button" value="Next Idea" />
	</form>
</div>
<div class="share">Please tag your images with #ysdidea so that I can find them!</div>
<div class="details">Developed by <a href="https://www.peralty.com">Malcolm Peralty</a></div>
</body>
</html>
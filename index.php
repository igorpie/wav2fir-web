<!-- SPIN FV-1 Decompiler v.04-->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>WAV to FIR</title>
</head>

<body style="background: rgb(192, 199, 209); font-family: 'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace;">
<div style="margin: 16px;">
<?php 

$module="index.php";

// get web variables by name, return values
function extract_get($input) {
    global $input,$debug;
    foreach ($input as $k) {
		if (isset($_REQUEST[$k])) {$GLOBALS[$k]=trim($_REQUEST[$k]);} 
		else {$GLOBALS[$k]="";};
		If ($debug==1) echo "  $k=",$GLOBALS[$k];
    }
}

// binary octet to string hex
function bin_hex8($a) {	
	$j = str_pad( strtoupper( dechex(ord($a)) ) ,2,"0",STR_PAD_LEFT); 
	return $j;
}


function decompile($i , $size_limit) {
	$i = substr ( $i , 44 , 3 * $size_limit);
	$i = str_split($i);
	//var_dump($i);
	$len=count($i);

	for ($x=0; $x < $len; $x+=3) {
		$b = ( ord($i[$x]) + 256*ord($i[$x+1]) + 65536*ord($i[$x+2]) );
		if ( ord($i[$x+2]) > 127) $b =  $b - 16777216; 		// convert negative 
		$r .= (double) ($b/8388608) ."\r\n";			// mantissa int / 2^22 
	}
	return $r;
}


function dbg($i , $size_limit) {
	$i = substr ( $i , 44 , 3 * $size_limit);
	$i = str_split($i);
	//var_dump($i);
	$len=count($i);

	for ($x=0; $x < $len; $x += 3) {
		$b = ( ord($i[$x]) + 256*ord($i[$x+1]) + 65536*ord($i[$x+2]) );
		if ( ord($i[$x+2]) > 127) $b =  $b - 16777216; 

		$hex24 = bin_hex8($i[$x+2])
			.bin_hex8($i[$x+1])
			.bin_hex8($i[$x+0]);
		$dbl = (double)($b/8388608.0);
		$r .= "0x$hex24 ".$dbl."\r\n";	// int / 2^23
	 }
	return $r;

}


function wav2c32($i , $size_limit){
	$i = substr ( $i , 44 , 3 * $size_limit);
	$i = str_split($i);
	$len = count($i);

	for ($x=0; $x < $len; $x += 3) {
		if ( $x%12 == 0) $r .= "\r\n";	//16 cols
		$hex24 = "0x".bin_hex8($i[$x+0]) .",0x". bin_hex8($i[$x+1]) .",0x". bin_hex8($i[$x+2]);
		if ( ord($i[$x+2]) > 127) $hex24 =  $hex24.",0xFF , "; // преобразование в отрицательное 
		else $hex24 = $hex24.",0x00 , ";
		$r .= "$hex24";
	 }
	return $r;
}


function wav2c16($i , $size_limit){

	$i = substr ( $i , 44 , 3 * $size_limit);
	$i = str_split($i);
	$len = count($i);

	for ($x=0; $x < $len; $x+=3) {
		if ( $x%24 == 0) $r .= "\r\n";	//16 cols
		$hex24 = "0x". bin_hex8($i[$x+1]) .",0x". bin_hex8($i[$x+2])." , ";
		$r .= "$hex24";
	 }
	return $r;
}



function proceed() {
	global $debug;
	$size_limit = 1000;
	if (trim($_FILES["FV_FILE"]["name"])=="") echo "File not chosen<br />"; 

	if ($_FILES["FV_FILE"]["error"] > 0)  echo "R Tape loading error 0:1" . $_FILES["FV_FILE"]["error"] . "<br />";
	else {
		$filename=$_FILES["FV_FILE"]["name"];
		$tmpname=$_FILES["FV_FILE"]["tmp_name"];
		$ext=substr($filename,-3,3);
		if ($debug) {
			echo "Upload: $filename<br>\n";
			echo "Type: " . $_FILES["FV_FILE"]["type"] . "<br>\n";
			echo "Size: " . ($_FILES["FV_FILE"]["size"] / 1024) . " kB<br>\n";
			echo "Temp file: $tmpname<br>\n";
			echo "Extension: $ext <br>\n";
		}
		
		
		$f=file_get_contents($tmpname);
		$o = decompile($f , $size_limit);
		$c32=wav2c32($f , $size_limit);
		$c16=wav2c16($f , $size_limit);

		echo "\n<br><h4> Results for $filename: </h4>";
		echo "\n<textarea   wrap=\"off\" cols=\"100\" rows=\"8\" style=\"background-color: #f8f8ff\" >$o</textarea>";
		echo "\n<br><br><i>Hint: </i>Ctrl+A , Ctrl+C in results area<br><br>";
		echo "\n<br><h4> C source (32 bit, ADAU1701): </h4>";
		echo "\n<textarea   wrap=\"off\" cols=\"100\" rows=\"8\" style=\"background-color: #f8f8ff\" >$c32</textarea><br><br>";
		echo "\n<br><h4> C source (16 bit of 24): </h4>";
		echo "\n<textarea   wrap=\"off\" cols=\"100\" rows=\"8\" style=\"background-color: #f8f8ff\" >$c16</textarea><br><br>";
//		$o1 = dbg($f ,  $size_limit);
//		echo "\n<br><h4> debug info: </h4>";
//		echo "\n<textarea   wrap=\"off\" cols=\"100\" rows=\"8\" style=\"background-color: #f8f8ff\" >$o1</textarea>";
	}
}


// main
echo "\n<br /><br><h3>24bit mono Wav to FIR coefficients converter by Igor</h3><br />";
$input = array('action');
extract_get($input); 

	echo "Choose .wav file<br><br>\n\n";
	echo "<form method=\"post\" action=\"$module\" enctype=\"multipart/form-data\">";
	echo "\n<input type=\"file\" name=\"FV_FILE\" size=\"50\" value=\"\" ><br /><br />";
	echo "\n<input type=\"submit\" name=\"name1\" value=\"Upload\">"; 
	echo "\n<input type=\"hidden\" name=\"action\" value=\"add\">";
	echo "</form>\n";

	
if ($action=="add")  proceed() ;	//2nd start - decompile


?>
<hr>
Change log:<br>
28-feb-2023 little refactoring (<a href = "https://github.com/igorpie/wav2fir-web">source</a>)  <br>
08-sep-2017 wav2hex (ADAU1701) added  <br>
06-may-2017 Only first 1000 coefficients out for adau1701  <br>
</div>
</body>
</html>

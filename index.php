<pre>
<?php
	//scrapes all of the development information from the massive dev chart
	
	//get list of flims
	$films = file_get_contents("http://www.digitaltruth.com/devchart.php");
	$films = explode('<option value="">All Films</option>', trim($films));
	$films = strip_tags($films[1]);
	$films = array_filter(explode(PHP_EOL, $films));

	
	$fp = fopen('file.csv', 'w');//open the output file
	
	
	foreach ($films as &$film_page) {//iterate through the pages for all flims
		$data = file_get_contents("http://www.digitaltruth.com/devchart.php?Developer=&mdc=Search&TempUnits=F&Film=" . urlencode($film_page));//download the page with the film/developer combos
	    $data = strip_tags($data, "<table><tr><td><th>");//remove all tags execept for tables
	    
	    $data = explode("<table cellspacing='0' frame='box' rules='all' class='mdctable'>", $data);
	    $data = $data[1];
	    
	    $data = explode("</table>", $data);
	    $data = $data[0];
	    
	    $data = str_replace(array(
	        " class='left'",
	        " class='center'",
	        " class='left nobr'"
	    ), "", $data);
	    $data = str_replace("</tr>", "</tr>\n", $data);
	    $data = trim($data);
	    
	    
	    $data = str_replace("</td><td>", "~", $data);
	    $data = str_replace(array(
	        "<tr>",
	        "</tr>",
	        "<td>",
	        "</td>"
	    ), "", $data);
	    
	    $data = explode(PHP_EOL, $data);
	    array_shift($data);
	    
	    
	    
	    foreach ($data as &$value) {
	        $film = explode("~", $value);
	        for ($i = 3; $i <= 6; $i++) {
	            if (!is_numeric($film[$i]) && !strpos($film[$i], "+") && strpos($film[$i], "-")) {
	                $avg      = explode("-", $film[$i]);
	                $film[$i] = ($avg[0] + $avg[1]) / 2;
	            }
	        }
	        fputcsv($fp, $film);
	        echo $film[0] . " -- " . $film[1] . "<br>";
	    }
	    
	}
	fclose($fp);
?>
<pre>

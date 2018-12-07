<pre>
<?php

// scrapes all of the development information from the massive dev chart

$debug = false; //enables a little bit of extra variable output to help debug
$linebreak = "\n"; //seems to need '\n' on windows while 'PHP_EOL' works on linux

$base_url="https://www.digitaltruth.com/devchart.php?Developer=&mdc=Search&TempUnits=C&TimeUnits=T&Film=";


$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
);  



$films = file_get_contents("http://www.digitaltruth.com/devchart.php", false, stream_context_create($arrContextOptions)); //get list of flims
$films = explode('<option value="">All Films</option>', trim($films)); //get the list of films by itself
$films = preg_replace("/.+\"(.+)\".+/", "$1", $films[1]); //get the film name selector value
$films = array_filter(explode($linebreak, $films)); //remove empty lines from the array

if ($debug)
{
    echo "List of films scraped:<br>";
    print_r($films);
}

$fp = fopen('file.csv', 'w'); //open the output file
fputcsv($fp, array("Film","Developer","Dilution","ASA/ISO","35mm","120","Sheet","Temp","Notes")); //add header

$counter = 1; //count the output for progress

foreach($films as & $film_page)
{ //iterate through the pages for all flims

		$scrape_url=$base_url . urlencode($film_page);

  	if ($debug)
  	{
	    echo "<a href=\"$scrape_url\">$scrape_url</a><br>";
		}

    $data = file_get_contents($scrape_url, false, stream_context_create($arrContextOptions)); //download the page with the film/developer combos
    $data = strip_tags($data, "<table><tr><td><th><a>"); //remove all tags execept for tables
	  $data = preg_replace('/\s?class=".*?"/', '', $data); //remove all classes
		$data = preg_replace("/\s?class='.*?'/", "", $data); //remove all classes
    $data = explode("<table>", $data); //get rid of the stuff before the table
    $data = $data[1];
    $data = explode("</table>", $data); //get rid of the stuff after the table
    $data = $data[0];
    $data = str_replace("</tr>", "</tr>\n", $data); //add linebreaks after each table row
    $data = str_replace("</td><td>", "~", $data); //replace the table tags with a delimiter
    $data = str_replace(array("<tr>","</tr>","<td>","</td>") , "", $data); //get rid of extra html
    $data = trim($data); //get rid of white space
    $data = explode($linebreak, $data); //split the data up by linebreaks
    array_shift($data); //get rid of the header
    foreach($data as & $value)
    { //loop through each item in the array of flim/dev combos
        $film = explode("~", $value); //explode by the delimiter
        for ($i = 3; $i <= 6; $i++)
        { //some items in the massive dev chart have a range of 6-8. i'm averaging all of these, so 6-8 would just turn into 7.
            if (!is_numeric($film[$i]) && !strpos($film[$i], "+") && strpos($film[$i], "-"))
            { //check to see if the data has a hyphen.
                $avg = explode("-", $film[$i]); //split by the hypen
                $film[$i] = ($avg[0] + $avg[1]) / 2; //average the number
            }
        }

				$notes=preg_replace("/[^0-9 ]/", '', $film[8]);
				if(is_numeric($notes)){
					$film[8] = "https://www.digitaltruth.com/devchart.php?devrow=$notes";
				}else{
					$film[8] = "";
				}

        fputcsv($fp, $film); // output that line to the output CSV
        echo $counter . " -- " . $film[0] . " -- " . $film[1] . "<br />"; //output some text as a progress indicator
        if ($debug)
        {
            echo "Raw film information: $value<br />\n";
            echo "Processed film information:";
            print_r($film);
            echo "<br />\n<hr>\n";
        }

        $counter++;
    }

}

fclose($fp); //close the output file.

?>
<pre>

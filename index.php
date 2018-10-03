<pre>
<?php

// scrapes all of the development information from the massive dev chart

$debug = false; //enables a little bit of extra variable output to help debug
$linebreak = "\n"; //seems to need '\n' on windows while 'PHP_EOL' works on linux
$films = file_get_contents("http://www.digitaltruth.com/devchart.php"); //get list of flims
$films = explode('<option value="">All Films</option>', trim($films)); //get the list of films by itself
$films = preg_replace("/.+\"(.+)\".+/", "$1", $films[1]); //get the film name selector value
$films = array_filter(explode($linebreak, $films)); //remove empty lines from the array

if ($debug)
{
    echo "<hr>List of films scraped<hr>\n";
    print_r($films);
}

$fp = fopen('file.csv', 'w'); //open the output file
fputcsv($fp, array(
    "Film",
    "Developer",
    "Dilution",
    "ASA/ISO",
    "35mm",
    "120",
    "Sheet",
    "Temp",
    "Notes"
)); //add header
$counter = 1; //count the output for progress

foreach($films as & $film_page)
{ //iterate through the pages for all flims
    $data = file_get_contents("http://www.digitaltruth.com/devchart.php?Developer=&mdc=Search&TempUnits=F&Film=" . urlencode($film_page)); //download the page with the film/developer combos
    $data = strip_tags($data, "<table><tr><td><th>"); //remove all tags execept for tables
    $data = explode("<table cellspacing='0' frame='box' rules='all' class='mdctable'>", $data); //get rid of the stuff before the table
    $data = $data[1];
    $data = explode("</table>", $data); //get rid of the stuff after the table
    $data = $data[0];
    $data = str_replace(array(
        " class='left'",
        " class='center'",
        " class='left nobr'"
    ) , "", $data); //remove the table attributes.
    $data = str_replace("</tr>", "</tr>\n", $data); //add linebreaks after each table row
    $data = str_replace("</td><td>", "~", $data); //replace the table tags with a delimiter
    $data = str_replace(array(
        "<tr>",
        "</tr>",
        "<td>",
        "</td>"
    ) , "", $data); //get rid of extra html
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

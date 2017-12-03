<?php 
/* ############################################################################
Builds RSS feed from a git repository.
############################################################################ */

/* ----- Load required files. ----- */

if (!file_exists("settings.php")) die ("Create a file 'settings.php'. You can copy 'settings_sample.php' from the same folder.");
include_once ("settings.php");

# Check if login is required
if ($settings['passwordFile'] != "") include ($settings['passwordFile']); 

$settings['homepage'] = trim($settings['homepage'], "/");

/* ----- Functions. ----- */

# Checks git log and reads it to an array
function getGitLog ($folder, $file) {
    if ($file != "") $command = 'git -C '.escapeshellarg($folder).' log '.escapeshellarg($file);
    else $command = 'git -C '.escapeshellarg($folder).' log';
    $output = shell_exec ($command);
    $output = array_diff(explode("commit", $output), array("", " "));

    $toReturn = array();

    foreach ($output as $version) {
        $version = explode (PHP_EOL, $version);
        $toReturn[] = array(
            "commit" => trim($version[0]),
            "author" => trim(str_replace("Author: ", "", $version[1])),
            "date" => strtotime(trim(str_replace("Date: ", "", $version[2])))
        );
    }

    return ($toReturn);

}

# Removes unwanted characters for links
function cleanIndexEntries ($input) {

    $input = str_replace(PHP_EOL, "%20", $input);
    $input = str_replace(" ", "%20", $input);
    return $input;

}

# Modifies commit message to be appropriate for titles
function getKeyTitle ($input) {

    $input = explode ("/", $input);
    $input = end($input);
    $input = str_replace("%20", " ", $input);
    $input = substr($input, 0, strrpos($input, "&"));
    return ($input);

}

// Sorting commits by time
function sortIndex ($a, $b) {
    return ($a["time"] > $b["time"]) ? -1 : 1;
}

// Function to get file edited for a commit
function getCommitedFile ($folder, $commit) {
    $command = "git -C ".escapeshellarg($folder)." diff-tree --no-commit-id --name-only -r ".escapeshellarg($commit);
    $output = shell_exec ($command);
    return trim($output);
}

// Function to get file edited for a commit
function getLastDiff ($folder, $commit, $file) {
    $command = "git -C ".escapeshellarg($folder)." --no-pager show ".escapeshellarg($commit).' '.escapeshellarg($file);
    $output = shell_exec ($command);
    return trim($output);
}

// Build array of pages sorted by the time they were last edited
$pagesandtime = array();
foreach (getGitLog($settings['sourceFolder'], "") as $page) {

    // Allow users to only get commits by a given committer
    if (isset($_GET['by']) and substr($page['author'], 0, strlen($_GET['by'])) != $_GET['by']) continue;

    // Get information on the commit
    $pagesandtime[getCommitedFile($settings['sourceFolder'], $page['commit']).'&amp;commit='.$page['commit']] = array("type" => "page", "time" => $page['date'], "author" => $page['author'], "description" => htmlspecialchars(getLastDiff($settings['sourceFolder'], $page['commit'], getCommitedFile($settings['sourceFolder'], $page['commit']))));
    
}

// Sort commits by time
uasort ($pagesandtime, "sortIndex");

/* ----- Output. ----- */

header('Content-Type: application/rss+xml');

echo <<< EOD
<rss version="2.0">
<channel>
    <title>{$settings['feedTitle']}</title>
    <link>{$settings['homepage']}</link>
    <description>{$settings['feedDescription']}.</description>
        
EOD;

$counter = 0;
foreach ($pagesandtime as $key => $value) {

    $printableKey = cleanIndexEntries($key);
    $keytitle     = getKeyTitle($printableKey);
    $date_r       = date("r", $value["time"]);
    $author       = substr($value['author'], 0, strpos($value['author'], " <"));

    if ($settings["showDiff"]) $description = $value['description'];
    else $description = "";

    echo <<< EOD

    <item>
        <title>$keytitle</title>
        <link></link>
        <guid>{$settings['homepage']}/?q={$printableKey}&amp;{$value['time']}</guid>
        <author>{$author}</author>
        <description>{$description}</description>
        <pubDate>{$date_r}</pubDate>
    </item>

EOD;

    $counter++; if ($counter >= 25) break;

}

echo PHP_EOL. '</channel>';
echo PHP_EOL. '</rss>';

?>


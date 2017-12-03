<?PHP

error_reporting(E_ALL);
ini_set("display_errors", 1);

$settings = array(
    "homepage"          => "https://example.com",                       # Homepage or link
    "title"             => "Project name",                              # Title of the website, project
    "feedTitle"         => "Feed title",                                # Title of the feed
    "feedDescription"   => "Feed description",                          # Title of the description
    "showDiff"          => true,                                        # true: Show git diff as description; false: do not
    "passwordFile"      => "",                                          # Leavve empty if no password is required
    "sourceFolder"      => "../../"                                     # Root folder of the git repo
);

?>

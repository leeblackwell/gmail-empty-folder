#!/usr/bin/php -q
<?php
$imap = null;

function script_empty_trash()
{
    global $user, $pass, $myfolder, $boolDelay, $intExpunge, $intProcessed, $intMax;
    $imap = @imap_open("{imap.gmail.com:993/imap/ssl/novalidate-cert}" . $myfolder, $user, $pass, NIL,
        10) or die("can't connect: " . imap_last_error() . "\n");
    if ($imap == false) {
        return false;
    }
    $mbox = @imap_check($imap);
    if ($mbox == false) {
        return false;
    }
    if ($mbox->Nmsgs > 1) {
        echo progress_bar(0, $mbox->Nmsgs, $info = "Starting ", $width = 50);
        for ($n = 1; $mbox->Nmsgs > $n; $n++) {
            if ($boolDelay) {
                usleep(500000); //sleep for half a sec
            }
            @imap_delete($imap, $n);
            echo progress_bar($n, $mbox->Nmsgs, $info = "Deleting  ", $width = 50);
            if ($n % $intExpunge == 0) {
                echo progress_bar($n, $mbox->Nmsgs, $info = "Expunging ", $width = 50);
                imap_expunge($imap);
                $n = 1;
                $mbox = @imap_check($imap);
            }
            $intProcessed++;
            if ($intMax > 0) {
                if ($intProcessed > $intMax) {
                    echo "Max reached - exiting.";
                    exit (0);
                }
            }
        }
        @imap_close($imap, CL_EXPUNGE);
        return false;
    } else {
        echo "Nothing to delete!" . PHP_EOL;
        @imap_close($imap, CL_EXPUNGE);
        return true;
    }
}


/* entry point */
function main($argc, $argv)
{
    global $user, $pass, $myfolder;
    $timeBegin = script_microtime();
    signal_handler_set('signal_handler');
    echo "Emptying " . $myfolder . "...\n";
    $blnResult = false;
    while (!$blnResult) {
        $blnResult = script_empty_trash();
    }
    $timeEnd = script_microtime();
    echo 'Executed in: ' . round($timeEnd - $timeBegin, 3) . " seconds\n";
    exit(0);
}

/* set signal handler if we can */
function signal_handler_set($handler)
{
    if (function_exists('pcntl_signal')) {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        } else {
            declare (ticks=1);
        }
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }
}

/* handle signals that can terminate the script; we want to die gracefully */
function signal_handler($signal)
{
    if ($signal == SIGTERM || $signal == SIGINT || $signal = SIGKILL) {
        echo "\nScript interrupted! Cleaning up...\n";
        exit(0);
    }
}

/* return a float for microtime() in order to time the script */
function script_microtime()
{
    list($msec, $sec) = explode(' ', microtime());
    return ((float)$msec + (float)$sec);
}

/* progress_bar
via: https://gist.github.com/mayconbordin
Note: No license specified at Github
*/
function progress_bar($done, $total, $info = "", $width = 50)
{
    $perc = round(($done * 100) / $total);
    $bar = round(($width * $perc) / 100);
    return sprintf("  %s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width - $bar),
        "[" . $done . "/" . $total . "] " . $info);
}

$temparr = explode('/', $argv[0]);
$me = array_pop($temparr);

$shortopts = '';
$shortopts .= "h::";
$longopts = array(
    "user:",        // Required: username
    "pass:",        // Required: password
    "folder::",     // Optional: folder to empty
    "delay",        // Optional: insert a 0.5s sleep between each call
    "max::",        // Optional: stop after processing n messages
    "expunge::"     // Optional: expunge after n messages
);
$options = getopt($shortopts, $longopts);

if (array_key_exists('h', $options)) {
    echo "$me\n\n";
    echo "Usage:\n";
    echo "--user jon.doe@gmail.com" . PHP_EOL;
    echo "      Required; login identity" . PHP_EOL;
    echo "--pass abc123" . PHP_EOL;
    echo "      Required; password" . PHP_EOL;
    echo "--folder MyFolderOfStuff" . PHP_EOL;
    echo "      Optional; folder to empty.  Default is [Gmail]/Bin" . PHP_EOL;
    echo "--delay" . PHP_EOL;
    echo "      Optional; inserts a 0.5s pause after each IMAP call; intended to reduce risk of rate limiting." . PHP_EOL;
    echo "--expunge 250" . PHP_EOL;
    echo "      Optional; expunge every n messages.  Default is 250." . PHP_EOL;
    echo "--max 999" . PHP_EOL;
    echo "      Optional; quit after processing n messages.  Default is to process all messages in the folder." . PHP_EOL;
    echo PHP_EOL;
    echo "Options with values must be specified PHP style, eg. --option=\"wibble\"" . PHP_EOL;
    echo PHP_EOL;
    exit(2);
}

$boolDelay = false;
$intExpunge = 250;
$myfolder = "[Gmail]/Bin";
$intMax = 0;
$intProcessed = 0;

if (!array_key_exists('user', $options)) {
    echo "user err" . PHP_EOL;
    exit(1);
}
if (!array_key_exists('pass', $options)) {
    echo "pass err" . PHP_EOL;
    exit(1);
}
if (array_key_exists('folder', $options)) {
    $myfolder = $options["folder"];
} else {
    $myfolder = "[Gmail]/Bin";
}
if (array_key_exists('delay', $options)) {
    $boolDelay = true;
}
if (array_key_exists('expunge', $options)) {
    $intExpunge = $options["expunge"];
}
if (array_key_exists('max', $options)) {
    $intMax = $options["max"];
}


$user = $options["user"];
$pass = $options["pass"];
print "User: " . $user . "\nPass: " . $pass . "\nFldr: " . $myfolder . "\nDelay: " . $boolDelay . "\nMax: " . $intMax . "\nExpunge: " . $intExpunge . "\n";

//exit (99);


main($argc, $argv);
echo PHP_EOL;
?>

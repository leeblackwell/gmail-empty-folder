#!/usr/bin/php -q
<?php
$imap = null;

function script_empty_trash()
{
    global $user, $pass, $myfolder, $boolDelay, $intExpunge, $intProcessed, $intMax, $intDelayLength;
    $intProcessed=0;

    $imap = @imap_open("{imap.gmail.com:993/imap/ssl/novalidate-cert}" . $myfolder, $user, $pass, NIL,
        10) or die("can't connect: " . imap_last_error() . "\n");
    if ($imap == false) {
        return false;
    }
    $mbox = @imap_check($imap);
    if ($mbox == false) {
        return false;
    }
    $initialmboxsize = $mbox->Nmsgs;
    if ($mbox->Nmsgs > 1) {
        echo status_bar(0, $initialmboxsize, 0, $info = "Starting ");
        for ($n = 1; $mbox->Nmsgs > $n; $n++) {
            if ($boolDelay) {
                usleep($intDelayLength); //sleep for half a sec
            }
            @imap_delete($imap, $n);
            echo status_bar($intProcessed, $initialmboxsize, $n, $info = "Deleting  " . $myfolder);
            if ($n % $intExpunge == 0) {
                echo status_bar($intProcessed, $initialmboxsize, $n, $info = "Expunging " . $myfolder);
                imap_expunge($imap);
                $n = 1;
                $mbox = @imap_check($imap);
            }
            $intProcessed++;
            if ($intMax > 0) {
                if ($intProcessed > $intMax) {
                    echo PHP_EOL."Max reached - exiting.".PHP_EOL;
                    return true;
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

function emptyfolder() {
    $timeBegin = script_microtime();
    $blnResult = script_empty_trash();
    $timeEnd = script_microtime();
    echo PHP_EOL . 'Executed in: ' . round($timeEnd - $timeBegin, 3) . " seconds\n";
    echo PHP_EOL;
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

function status_bar($done, $total, $current, $info = "")
{
    $width = 40;
    if ( $done >= $total ) $done = $total;
    $perc = round(($done * 100) / $total);
    $bar = round(($width * $perc) / 100);
    //echo "\n\$done ".$done."\t\$total ".$total."\t \$current ".$current."\n";
    return sprintf("  %s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width - $bar), " Total:" . $total . " Done:" . $done . " Current:" . $current . " " . $info . "\033[K");
}

$temparr = explode('/', $argv[0]);
$me = array_pop($temparr);

$shortopts = '';
$shortopts .= "h::";
$longopts = array(
    "user:",                // Required: username
    "pass:",                // Required: password
    "folder::",             // Optional: folder to empty
    "delay",                // Optional: insert a 0.5s sleep between each call
    "delaylength::",        // Optional: insert a 0.5s sleep between each call
    "max::",                // Optional: stop after processing n messages
    "expunge::",            // Optional: expunge after n messages
    "daemon",               // Daemon mode; sit in a forever loop
    "daemonsleep::"         // Daemon mode; how long to wait between each loop
);
$options = getopt($shortopts, $longopts);

if (array_key_exists('h', $options)) {
    echo "$me\n\n";
    echo "Usage:\n";
    echo "--user=jon.doe@gmail.com" . PHP_EOL;
    echo "      Required; login identity" . PHP_EOL;
    echo "--pass=abc123" . PHP_EOL;
    echo "      Required; password" . PHP_EOL;
    echo "--folder=MyFolderOfStuff" . PHP_EOL;
    echo "      Optional; folder to empty.  Default is [Gmail]/Bin" . PHP_EOL;
    echo "--delay" . PHP_EOL;
    echo "      Optional; inserts a 0.5s pause after each IMAP call; intended to reduce risk of rate limiting." . PHP_EOL;
    echo "--delaylength" . PHP_EOL;
    echo "      Optional; override 0.5s delay; express as microseconds - 500000=0.5s, " . PHP_EOL;
    echo "--expunge=250" . PHP_EOL;
    echo "      Optional; expunge every n messages.  Default is 250." . PHP_EOL;
    echo "--max=999" . PHP_EOL;
    echo "      Optional; quit after processing n messages.  Default is to process all messages in the folder." . PHP_EOL;
    echo "--daemon" . PHP_EOL;
    echo "      Optional; when finished, start over again." . PHP_EOL;
    echo "--daemonsleep=300" . PHP_EOL;
    echo "      Optional; if running in daemon mode, how many seconds to sleep inbetween each run (default is 300) " . PHP_EOL;
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
$intDelayLength = 500000;
$daemonmode = false;
$daemonsleep=300;

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
if (array_key_exists('delaylength', $options)) {
    $intDelayLength = $options["delaylength"];
}
if (array_key_exists('expunge', $options)) {
    $intExpunge = $options["expunge"];
}
if (array_key_exists('max', $options)) {
    $intMax = $options["max"];
}
if (array_key_exists('daemon', $options)) {
    $daemonmode = true;
}
if (array_key_exists('daemonsleep', $options)) {
    $daemonsleep = $options["daemonsleep"];
}


$user = $options["user"];
$pass = $options["pass"];

echo "Working on ".$myfolder.PHP_EOL;
if ( $daemonmode )  { echo "Daemon mode, ".$daemonsleep."s between each invocation.".PHP_EOL; }

global $user, $pass, $myfolder;
if ( $daemonmode ) {
    while ( $daemonmode ) {
        $null = emptyfolder();
        echo "Daemon mode; sleeping ".$daemonsleep."s";
        sleep($daemonsleep);
    }
} else {
    $null = emptyfolder();
}
exit(0);


?>

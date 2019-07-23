#!/usr/bin/php -q
<?php
$imap = null;
if ($argc < 3) {
    exit("Usage: program <parameter1>\n");
}
$myfolder = "[Gmail]/Bin";
$user = $argv[1];
$pass = $argv[2];
if ($argc > 3) {
    $myfolder = $argv[3];
}
print "User: " . $user . "\nPass: " . $pass . "\nFldr: " . $myfolder . "\n";

function script_empty_trash()
{
    global $user, $pass, $myfolder;
    //signal_handler_set(SIG_DFL);
    $imap = @imap_open("{imap.gmail.com:993/imap/ssl/novalidate-cert}" . $myfolder, $user, $pass, NIL,
        10) or die("can't connect: " . imap_last_error() . "\n");
    if ($imap == false) {
        return false;
    }
    $mbox = @imap_check($imap);
    if ($mbox == false) {
        return false;
    }
    if ($mbox->Nmsgs > 0) {
        //$bar = new Console_ProgressBar('- Deleting %fraction% [%bar%] %percent% ETA: %estimate%', '=>', '-', 74, $mbox->Nmsgs);
        echo progress_bar(0, $mbox->Nmsgs, $info="Starting ", $width=50);
        for ($n = 1; $mbox->Nmsgs > $n; $n++) {
            @imap_delete($imap, $n);
            echo progress_bar($n, $mbox->Nmsgs, $info="Deleting ", $width=50);
            if ($n % 250 == 0) {
                echo progress_bar($n, $mbox->Nmsgs, $info="Expunging ", $width=50);
                imap_expunge($imap);
                $n = 1;
                $mbox = @imap_check($imap);
            }
        }
        @imap_close($imap, CL_EXPUNGE);
        return false;
    } else {
        echo "Nothing to delete!";
        @imap_close($imap, CL_EXPUNGE);
        return true;
    }
    return false;
}



/* entry point */
function main($argc, $argv)
{
    global $user, $pass, $myfolder;
    /* display errors only */
    //error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
    /* set signal handlers and time execution of the core routines */
    $timeBegin = script_microtime();
    signal_handler_set('signal_handler');
    //script_list_boxes();
    echo "Emptying " . $myfolder . "...\n";
    $blnResult = false;
    while (!$blnResult) {
        $blnResult = script_empty_trash();
    }
    $timeEnd = script_microtime();
    echo 'Executed in: ' . round($timeEnd - $timeStart, 3) . " seconds\n";
    exit(0);
}

/* set signal handler if we can */
function signal_handler_set($handler)
{
    if (function_exists('pcntl_signal')) {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        } else {
            declare (ticks = 1);
        }
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
        #pcntl_signal(SIGKILL, $handler);
    }
}

/* handle signals that can terminate the script; we want to die gracefully */
function signal_handler($signal)
{
    if ($signal == SIGTERM || $signal == SIGINT || $signal = SIGKILL) {
        echo "\nScript interrupted! Cleaning up...\n";
        script_cleanup();
        exit(0);
    }
}

/* return a float for microtime() in order to time the script */
function script_microtime()
{
    list($msec, $sec) = explode(' ', microtime());
    return ((float) $msec + (float) $sec);
}
/* list all mail boxes on the gmail imap server
 * used for development, but not needed in the final version
 */

function script_list_boxes()
{
    global $user, $pass, $myfolder;
    $imap = imap_open("{imap.gmail.com:993/imap/ssl/novalidate-cert}", $user,
        $pass) or die("Cannot connect: " . imap_last_error() . "\n");
    $boxes = imap_list($imap, '{imap.gmail.com}', '*');
    print_r($boxes);
    imap_close($imap);
}

/* progress_bar
via: https://gist.github.com/mayconbordin
Note: No license specified at Github
*/
function progress_bar($done, $total, $info="", $width=50) {
    $perc = round(($done * 100) / $total);
    $bar = round(($width * $perc) / 100);
    return sprintf("  %s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width-$bar), "[".$done."/".$total."] ".$info);
}

main($argc, $argv);
?>

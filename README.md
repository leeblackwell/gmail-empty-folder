# gmail-empty-folder

Gmail seems to struggle with larger volumes of mail; once you get to more than a few thousand messages in trash or a label, deleting those items (or emptying trash) is broken.
That doesn't seem right to me; that is, the Gmail service is rate limited in it's own actions.

Meh... If you find yourself in one of these scenarios, I've found three solutions:

1. Manually select and delete a small (100 or so) messages at a time
1. Connect a fully fledged mail client via IMAP, then execute step 1
1. Delete the account completely and start again

None of these options are particularly appealing to me.

Using IMAP does work, but that /manual/ interaction? In the 21st century? Sheesh!
Enter 'gmail-empty-folder', a rough and ready PHP script that will perform the tedious task for you.

## Pre-requisites

### Gmail - application password
You're not gonna wanna use your regular password or disable 2FA (you do use 2FA, right?), so create an application password.  [Google will tell you how to do this](https://support.google.com/accounts/answer/185833?hl=en).

## Direct execution

To run directly, execute:

```
gmail-cleanup.php --user=USER --pass=PASS --folder=FOLDER
```

Where:
1. USER is your google login; eg `somebody@gmail.com`
1. PASS is the application password you created in the previous step; eg `fgdu65rkjhgjhfd53`
1. FOLDER is optional.  If not specified, we'll attempt to empty the trash.  If specified, we'll empty that folder instead. eg `@FilteredNoiseThatIsntWanted`

Output will look something like this:
```
  1%[>                                        ] Total:822 Done:7 Current:8 Deleting  [Gmail]/Bin
```

Oh, yeah.  173k to delete.  This is why this script exists ;)

For January 2020, there are some new options:
1. `--delay` Inserts a 0.5s pause after each message is processed; intended to try and avoid Google's rate limiting
1. `--delaylength=500000` Override the default delay length to n microseconds
1. `--max=999` Quits after processing n messages
1. `--expunge=500` Expunge after n messages

## Docker container

This script works well inside a container; there's a version available via [Docker Hub](https://hub.docker.com/r/leeblackwell/gmail-empty-folder).

If you wish to pre-fetch the container image:
```
docker pull leeblackwell/gmail-empty-folder
```

You'll need to pass in username, password and (optionally) folder as environment variables:  
1. `GUSER="somebody@gmail.com"`
1. `GPASS="fgdu65rkjhgjhfd53"`
1. `GFLDR="@FilteredNoiseThatIsntWanted"`
1. `FLAGS="--delay --max=5000 --expunge=333"`

The FLAGS env variable is optional; it may be used for one or more of the additional options.

If you choose to use GFLDR or FLAGS, duplicate the `--env` statements for GFLDR and FLAGS in the docker command lines below.

To run in the background as a daemon:

```
docker container run --restart=always -d --env GUSER="somebody@gmail.com" --env GPASS="fgdu65rkjhgjhfd53" --name gmail-trash leeblackwell/gmail-empty-folder
```

By 'naming' the container, you can run `docker container stop gmail-trash` and/or `docker container start gmail-trash` at your leisure.

For a 'live' session:

```
docker container run -it --env GUSER="somebody@gmail.com" --env GPASS="fgdu65rkjhgjhfd53" leeblackwell/gmail-empty-folder
```



## A word of warning

The same rate-limiting rules that appear to be present for Gmail itself, also apply to IMAP activities.  It's entirely possible that if you run several sessions concurrently, Google will revoke the application password and put your account on lockdown (forced to change pass, validate ID etc) - at least, they did to me.  I was running five containers operating on five different folders; maybe that's too much for Google.
You might be best to run one at a time. Either way, YMMV.

## Upcoming OAuth requirements

I'm aware that [Google is going to force "less secure apps" to use OAuth](https://gsuiteupdates.googleblog.com/2019/12/less-secure-apps-oauth-google-username-password-incorrect.html) - it's on the TODO list :) 

## Disclaimer

This is a hobby project; use at your own risk and you're *definitely* entirely and completely responsible for data loss (given that's what this thing is designed to do) as well as your choice allow this script to access your mailbox.

## Thanks

Thanks to [Neil Innes](https://github.com/NeilInnes) who had already written this script (used with permission!); I simply tweaked and containerized it.

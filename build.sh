#!/bin/bash

IMG=""
REGHOST="hostgoeshere"
REGPORT=443
BUILD=0
PUSH=0
CACHEFLAG=""
SAMEDIR=0
HELP=0
ME=$(basename $0)

while [[ $# -gt 0 ]]
do
	key="$1"
	case $key in
		--reghost)
		REGHOST=$2
		shift # past argument
		shift # past value
		;;
		--regport)
		REGPORT=$2
		shift # past argument
		shift # past value
		;;
		--img)
		IMG=$2
		shift # past argument
		shift # past value
		;;
		--tag)
		TAG=$2
		shift # past argument
		shift # past value
		;;
		--build)
		BUILD=1
		shift # past argument
		;;
		--push)
		PUSH=1
		shift # past argument
		;;
		--nocache)
		CACHEFLAG="--no-cache"
		shift # past argument
		;;
		--help)
		HELP=1
		shift # past argument
		;;
		-h)
		HELP=1
		shift # past argument
		;;
		*)    # unknown option
		POSITIONAL+=("$1") # save it in an array for later
		shift # past argument
		;;
	esac
done
set -- "${POSITIONAL[@]}" # restore positional parameters

#Is a tag defined by env var? If so, use it...
if [ ! -z "$DOCKERTAG" ]; then
	TAG="$DOCKERTAG"
elif [ -z "$TAG" ]; then
	TAG=$(whoami)
fi

#Show help if necessary
if [ "$HELP" -gt 0 ]; then
	echo "Usage: ${ME} [OPTIONS]"
	echo ""
	echo "Options:"
	echo "    --img examplehere      Specify the name of the container image to build;"
	echo "                           expected to be a directory name in the same location"
	echo "                           as ${ME}"
	echo "    --build                Build the image"
	echo "    --push                 Push the image to the docker registry"
	echo "    --reghost hostname     Override the registry host (default is ${REGHOST})"
	echo "    --regport 99999        Override the registry port (default is ${REGPORT})"
	echo "    --nocache              Force docker to ignore local cache"
	echo ""
	echo "--img is mandatory; Either --build or --push (or both) must be specified."
	echo ""
	echo "Environment variables:"
	echo ""
	echo "DOCKERTAG                  When an image is built, it will be tagged with \$DOCKERTAG"
	echo "                           The default is the username ($TAG in this invocation)"
  echo ""
	exit 2
fi

if [ -z "$IMG" ]; then
	echo "Directory/target not provided."
	exit 1
fi
if [[ $BUILD -eq 0 && $PUSH -eq 0 ]]; then
	echo "No flag for build or push specified."
	exit 1
fi

if [ $BUILD -gt 0 ]; then
	T=$(basename $(pwd))
	docker build $CACHEFLAG -t ${T}:${TAG} .
	RES="$?"
	echo "docker build returned $RES"
	if [ "$RES" -ne 0 ]; then 
		exit 1
	fi
fi

if [ $PUSH -gt 0 ]; then
	T=$(basename $(pwd))
	nc -zw5 $REGHOST $REGPORT
	if [ $? -eq 0 ]; then
		docker tag ${T}:${TAG} ${REGHOST}/${T}:${TAG}
		docker push ${REGHOST}/${T}:${TAG}
	else
		echo "**WARN: $REGHOST not reachable; not pushing..."
	fi
fi

exit 0

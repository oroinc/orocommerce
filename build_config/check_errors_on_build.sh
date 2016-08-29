#!/bin/bash -e

log_file_path="app/logs/prod.log"

while getopts "p:" opt; do
    case "$opt" in
    p)  log_file_path=$OPTARG
        ;;
    esac
done

if [[ $(cat $log_file_path | sed -e /^$/d | grep -nv "404" | grep -nv "Access denied" | wc -l) = 0 ]]; then
    exit 0
else
    echo "There are one or more errors on build are founded. Please see $log_file_path"
    exit -1
fi

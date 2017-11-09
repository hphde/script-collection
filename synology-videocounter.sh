#!/bin/bash
# Goal was to know the numbers of Videos created over night from the Synology Surveillance
# Just create a job that runs every morning and configure it to send out an email

DATEFORMAT="%Y%m%d"
VIDEOS="/volume1/surveillance/Camera"
YESTERDAY=$(date -d "$(date) - 1 day" +${DATEFORMAT})
TODAY=$(date +${DATEFORMAT})
NUMBER=$(ls ${VIDEOS}/${TODAY}AM ${VIDEOS}/${YESTERDAY}PM|grep -c '.mp4')

echo "Number of videos for the night of ${YESTERDAY}/${TODAY}: ${NUMBER}"

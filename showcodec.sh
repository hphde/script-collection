#!/bin/bash
# Heinz Peter Hippenstiel github.hph@xoxy.net
#
# My PS4 media player is picky about some audio/video codecs
# I didn't want to click through since I knew what it doesn't like
# ffprobe from the ffmpeg package can show the info but the output is ... exhausting
# well - I made a wrapper for my needs
#
for i in "$@"; do
  [[ ! -f $i ]] && continue # to parse if not a regular file
  streams=$(ffprobe "$i" 2>&1|grep -oP 'Stream #.+?:.+?: \K(Video|Audio): .+')
  v1=$(echo "$streams"|grep -oP 'Video: \K.+? ')              # codec
  v2=$(echo "$streams"|grep -oP 'Video: .+, \K[0-9]+x[0-9]+') # dimension
  #echo "$streams" # debug
  echo -n "File: $i"
  echo -n " # Videocodec: ${v1}/ ${v2}"
  # parse every audio stream
  while read l; do
    a1=$(echo "$l"|grep -oP 'Audio: \K[^ ,]+')              # codec
    a2=$(echo "$l"|grep -oP 'Audio: .+?, \K.+? Hz')         # samplerate
    a3=$(echo "$l"|grep -oP 'Audio: .+?, .+? Hz, \K[^(,]+') # channels
    echo -n " # Audiocodec: ${a1} / ${a2} / ${a3}"
  done <<< "$(echo "$streams"|grep Audio)"
  echo
done

#!/bin/bash
# For some purposes I had the need to completely copy one GHE orgs teams to another. I wrote this shell script to do that job. 
# It takes care of the privacy and the description. It's rather basic but checks if source and destination orgs exist.
# If you have more than 100 teams in one org then the script will not work because it doesn't do pagination. Also it doesn't catch any error but it's failsafe to just rerun the script.
# Requires curl and jq to run properly. Should run under bash and zsh

SRC_ORG=${SRC_ORG:-the-source-org}
DST_ORG=${DST_ORG:-the-destination-org}
TOKEN=${TOKEN:-1234567890abcdefghijklmnopqrstuvwyxz}
BASE='https://api.github.com/orgs/'

shopt -s expand_aliases
alias curl='curl -s -k -H "Authorization: token ${TOKEN}" -H "Accept: application/vnd.github.hellcat-preview+json"'

check() {
  if [[ "$(curl $1|jq -r '.message?')" == "Not Found" ]]; then
    echo ">$1< Not found - please check!"
    exit 1
  fi
}

loop() {
  FILTER="=="
  if [ "$1" == "child" ];then
    FILTER="!="
  fi

  SRC="$BASE$SRC_ORG/teams"
  DST="$BASE$DST_ORG/teams"
  check "$SRC"
  check "$DST"
  
  TEAMATTR='name: .name,description: .description,privacy: .privacy'

  while read l; do
    team="$(curl $SRC/$l)"
    if [[ $(echo "$team"|jq -r '.parent.slug') == "null" ]]; then
      echo -n "No parent "
      team=$(echo "$team"|jq -c '.|{'"$TEAMATTR"'}')
      echo "$team"
    else
      echo -n "Has parent"
      parent=$(curl $DST/$(echo "$team"|jq -r '.parent.slug')|jq -c '.id')
      team=$(echo "$team"|jq -c '.|{'"$TEAMATTR"',parent_team_id: '"${parent}"'}')
      echo "$team"
    fi
    
    curl -d "$team" $DST|jq '.message'
  done <<< "$(curl $SRC'?per_page=100'|jq -r '.[]|select(.parent'"$FILTER"'null)|.slug')"
}

echo "Looping over parents..."
loop
echo "Looping over childs..."
loop child

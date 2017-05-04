#!/bin/bash

user="gitlab-rp"
password="k4KiBo8kuLQkp8NX"

# Bot access token
BOT_TOKEN="304380175:AAF2zD5EhoH1JZBaa2QZmtTBTnGfhAtqsfA"
BOT_API=$(printf "https://api.telegram.org/bot%s/" "$BOT_TOKEN")

# messga template like%
# for in progress - Issue: GPI-2, set status: in progress, branch name: GPI-2/start-issue, at: %d-%m by AUTHOR
# for review - Issue: GPI-2, set status: review, branch name: GPI-2/start-issue, at: %d-%m by AUTHOR
# for await for build - Issue: GPI-2, set status: await for build, branch name: GPI-2/start-issue, at: %d-%m by AUTHOR

MESSAGE_TEMPLATE_ISSUE="BuildID: <a href='%s' title='BUILD-%s'>%s</a>, cache was cleared on %s environment"

GET_ME=$(printf "${BOT_API}%s" "getMe")
POST_MESSAGE=$(printf "${BOT_API}%s" "sendMessage")
GET_UPDATES=$(printf "${BOT_API}%s" "getUpdates")

CHATS=($(curl -XGET "${GET_UPDATES}" | jq -r ".result[].message.chat.id" | sort -u))

BUILD_LINK="$1"
BUILD_ID="$2"
BUILD_ENV="$3"
SECOND_COMMIT="$4"

DIR="$5"

cd $DIR

if [[ -d "vendor" ]]; then
	/usr/bin/php app/console redis:flushall --env=$BUILD_ENV --no-interaction
	/usr/bin/php app/console cache:clear --env=$BUILD_ENV
	/usr/bin/php app/console assets:install web --symlink --relative --env=$BUILD_ENV

	MESSAGE_ISSUE=$(printf "$MESSAGE_TEMPLATE_ISSUE" "$BUILD_LINK" "$BUILD_ID" "$BUILD_ID" "$BUILD_ENV")
	for chat_id in "${CHATS[@]}"; do
	    curl -D- -XPOST -H "Content-Type: application/json" "${POST_MESSAGE}" --data '{
	        "chat_id": "'"${chat_id}"'",
	        "text": "'"${MESSAGE_ISSUE}"'",
	        "parse_mode": "HTML"
	    }'
	done
fi
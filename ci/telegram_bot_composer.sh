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

MESSAGE_TEMPLATE_ISSUE="BuildID: <a href='%s' title='BUILD-%s'>%s</a>, composer was installed"

GET_ME=$(printf "${BOT_API}%s" "getMe")
POST_MESSAGE=$(printf "${BOT_API}%s" "sendMessage")
GET_UPDATES=$(printf "${BOT_API}%s" "getUpdates")

CHATS=($(curl -XGET "${GET_UPDATES}" | jq -r ".result[].message.chat.id" | sort -u))

BUILD_LINK="$1"
BUILD_ID="$2"
SECOND_COMMIT="$3"
DIR="$4"

cd $DIR

# проверяем на наличие команд по композеру
if [[ ! -d "vendor" && -a "composer.json" ]]; then
	/usr/bin/php /usr/local/bin/composer install
	   
else
	/usr/bin/php /usr/local/bin/composer update
fi

MESSAGE_ISSUE=$(printf "$MESSAGE_TEMPLATE_ISSUE" "$BUILD_LINK" "$BUILD_ID" "$BUILD_ID")
for chat_id in "${CHATS[@]}"; do
    curl -D- -XPOST -H "Content-Type: application/json" "${POST_MESSAGE}" --data '{
        "chat_id": "'"${chat_id}"'",
        "text": "'"${MESSAGE_ISSUE}"'",
        "parse_mode": "HTML"
    }'
done 


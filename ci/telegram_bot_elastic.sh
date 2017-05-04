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

MESSAGE_TEMPLATE_ISSUE="Elastica index was reset and populate all data from BUILD: %s"

GET_ME=$(printf "${BOT_API}%s" "getMe")
POST_MESSAGE=$(printf "${BOT_API}%s" "sendMessage")
GET_UPDATES=$(printf "${BOT_API}%s" "getUpdates")

CHATS=($(curl -XGET "${GET_UPDATES}" | jq -r ".result[].message.chat.id" | sort -u))

COMMIT_MSG="$1"
BUILD_ID="$2"

# проверяем на наличие команд по еластику
if [[ ! -n $(echo "$COMMIT_MSG" | sed -n 's/\(#elastic\)/\1/p') ]]; then
	# split by awk - echo "$COMMIT_MSG" | awk '{split($0,types,":")} END {for(n in numbers){ print numbers[n] }}'
	
	# collect types for index - sed 's/.*#elastic\([^,\.]*\)\(.*\)*/\1/'
	# typesElastic=`echo $types | awk 'BEGIN {FS="|"} {for(i=1;i<=NF;i++) system("'"$phpBin"' app/console fos:elastica:populate --index=russianplace --type="$i)}'`
	
	
	phpBin=$(which php)
	# вырезаем все типы для индекса и помещаем в types
	types=$(echo "$COMMIT_MSG" | sed 's/^[ \t]*//;s/[ \t]*$//' | sed 's/.*#elastic\([^,\.]*\)\(.*\)*/\1/')
	
	if [[ ! -n "$types" ]]; then
		# Формируем массив типов для еластика
		typesElastic=$(echo $types | awk 'BEGIN {FS="|"} {for(i=1;i<=NF;i++) system("'"$phpBin"' app/console fos:elastica:populate --index=russianplace --type="$i)}')
		
		# ${$phpBin} app/console fos:elastica:reset --index=russianplace
		for _type in "${typesElastic[@]}"; do
			"${_type} > /dev/null"
	    done
	    
	    MESSAGE_TEMPLATE_ELASTIC="Elastica index was reset and populate types %s, from BUILD: %s"
	    	
		MESSAGE_ISSUE=$(printf "$MESSAGE_TEMPLATE_ELASTIC" "$types" "$BUILD_ID")
		for chat_id in "${CHATS[@]}"; do
		    curl -D- -XPOST -H "Content-Type: application/json" "${POST_MESSAGE}" --data '{
		        "chat_id": "'"${chat_id}"'",
		        "text": "'"${MESSAGE_ISSUE}"'",
		        "parse_mode": "HTML"
		    }'
		done
	else
		MESSAGE_ISSUE="Пустой список типов для индексации"
		for chat_id in "${CHATS[@]}"; do
		    curl -D- -XPOST -H "Content-Type: application/json" "${POST_MESSAGE}" --data '{
		        "chat_id": "'"${chat_id}"'",
		        "text": "'"${MESSAGE_ISSUE}"'",
		        "parse_mode": "HTML"
		    }'
		done
	fi
fi
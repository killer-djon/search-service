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

MESSAGE_TEMPLATE_ISSUE="Issue: <a href='%s' title='%s'>%s</a>, set status: %s, branch name: %s, at: %s, message: %s"
MESSAGE_TEMPLATE_BUILD="Build was created with build link on GitLab: <a href='%s' title='Build link'>BuildID: %s</a>"

GET_ME=$(printf "${BOT_API}%s" "getMe")
POST_MESSAGE=$(printf "${BOT_API}%s" "sendMessage")
GET_UPDATES=$(printf "${BOT_API}%s" "getUpdates")

CHATS=($(curl -XGET "${GET_UPDATES}" | jq -r ".result[].message.chat.id" | sort -u))

FIRST_COMMIT="$1"
SECOND_COMMIT="$2"
BUILD_DIR="$3"
HOME_DIR="$4"


BRANCH_NAME=$(echo $FIRST_COMMIT | cut -d\' -f2)

# Находим референс (ID тикета)
# если его нет значит берем название из $BRANCH_NAME
if [[ -n $(echo "$SECOND_COMMIT" | sed -n 's/\(#ref\)/\1/p') ]]; then
    ISSUE_NAME=$(echo "$SECOND_COMMIT" | cut -d',' -f 2 | sed -e 's/\#ref*//' | sed 's/^[ \t]*//;s/[ \t]*$//')
else
    ISSUE_NAME=$(echo "$BRANCH_NAME" | cut -d'/' -f 1)
fi

JIRA_MESSAGE=$(echo "$SECOND_COMMIT" | sed -E 's#^.*, (.*)$#\1#g' | sed 's/^[ \t]*//;s/[ \t]*$//')
JIRA_ISSUE="$ISSUE_NAME"

# commit like: #action in progress, #ref GPI-3, Start new integration
if [[ -n $(echo "$SECOND_COMMIT" | sed -n 's/\(#action\)/\1/p') ]]; then
    JIRA_ACTION=$(echo "$SECOND_COMMIT" | cut -d',' -f 1 | sed -e 's/\#action*//' |  sed 's/^[ \t]*//;s/[ \t]*$//')
else
    # Get action from branch commmit
    # CI_BUILD_REF_NAME - name of the branch
    JIRA_ACTION=$(curl -XGET -u $user:$password 'https://jira.russianplace.com/rest/api/2/issue/'"${JIRA_ISSUE}"'?fields=status' | jq -r '.fields.status.name')
fi

JIRA_PROJECT=$(curl -XGET -u $user:$password 'https://jira.russianplace.com/rest/api/2/issue/'"${JIRA_ISSUE}"'?fields=project' | jq -r '.fields.project.key')

JIRA_ISSUE_LINK="https://jira.russianplace.com/projects/${JIRA_PROJECT}/issues/${JIRA_ISSUE}"

# create message from template
MESSAGE_ISSUE=$(printf "$MESSAGE_TEMPLATE_ISSUE" "$JIRA_ISSUE_LINK" "$JIRA_ISSUE" "$JIRA_ISSUE" "$JIRA_ACTION" "$BRANCH_NAME" "$(date +%Y-%m-%d:%H:%M:%S)" "$JIRA_MESSAGE")


for chat_id in "${CHATS[@]}"; do
    curl -D- -XPOST -H "Content-Type: application/json" "${POST_MESSAGE}" --data '{
        "chat_id": "'"${chat_id}"'",
        "text": "'"${MESSAGE_ISSUE}"'",
        "parse_mode": "HTML"
    }'
done

mkdir -p "${HOME_DIR}/CACHE-BUILDS-API"

for i in $(ls -1t "${BUILD_DIR}" | tail -n +11); 
do 
	mv "${BUILD_DIR}/${i}" "${HOME_DIR}/CACHE-BUILDS-API/${i}"
done
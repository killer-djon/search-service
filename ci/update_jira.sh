#!/bin/bash

declare -A TRANS
# set to in progress transition
# transition id by 241 
# set status from "select for develpment" to "in progress"
TRANS["in progress"]="241"
TRANS["progress"]="241"
TRANS["inprogress"]="241"
TRANS["start"]="241"

# set to review transition
# transition id by 171 
# set status from "in progress" to "review"
TRANS["in review"]="171"
TRANS["review"]="171"
TRANS["inreview"]="171"


# set to resolved transition
# transition id by 121
# set status from "review" to "await for build"
TRANS["in resolve"]="121"
TRANS["inresolve"]="121"
TRANS["resolve"]="121"
TRANS["resolved"]="121"
TRANS["resolves"]="121"
TRANS["fixed"]="121"
TRANS["close"]="121"
TRANS["closed"]="121"

user="gitlab"
password="k4KiBo8kuLQkp8NX"

FIRST_COMMIT="$1"
SECOND_COMMIT="$2"

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
    JIRA_ACTION=$(curl -XGET -u $user:$password "https://jira.russianplace.com/rest/api/2/issue/${JIRA_ISSUE}?fields=status" | jq -r '.fields.status.name')
fi

JIRA_COMMIT_MESSAGE="Push commit: ${JIRA_MESSAGE}<br /> Time commit: $(date +%Y-%m-%d:%H:%M:%S)<br> Branch name: ${BRANCH_NAME}<br> Status jira issue: ${JIRA_ACTION}"

curl -D- -u $user:$password -X PUT -H "Content-Type: application/json" "https://jira.russianplace.com/rest/api/2/issue/${JIRA_ISSUE}" --data '{"update": {"comment": [{"add": {"body": "'"${JIRA_COMMIT_MESSAGE}"'"}}]},"fields": {"customfield_10100": "'"${BRANCH_NAME}"'"}}'

curl -D- -u $user:$password -X POST -H "Content-Type: application/json" "https://jira.russianplace.com/rest/api/2/issue/${JIRA_ISSUE}/transitions?expand=transitions.fields" --data '{
	"transition": {
		"id": '"${TRANS[${JIRA_ACTION}]}"'
	}
}'
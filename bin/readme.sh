#!/usr/bin/env bash
short_name="pmpro-import-members-from-csv"
server="eighty20results.com"
sed="$(which sed)"
readme_path="./build_readmes/"
changelog_source=${readme_path}current.txt
changelog_out="CHANGELOG.md"
wordpress_version=$(wget -q -O - http://api.wordpress.org/core/stable-check/1.0/  | grep latest | awk '{ print $1 }' | sed -e 's/"//g')
tmp_changelog=$(mktemp /tmp/chlog-XXXXXX)
# stripped_log=$(mktemp /tmp/old-info-XXXXXX)
version=$(grep -E "^Version:" ./class.pmpro-import-members.php | sed 's/[[:alpha:]|(|[:space:]|\:]//g' | awk -F- '{printf "%s", $1}')
today=$(date +%Y-%m-%d)
changelog_new_version="## v${version} - ${today}"
changelog_header=$(cat <<- __EOF__
# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


__EOF__
)

###########
#
# Update plugin and wordpress version info in README.txt
#
if [[ -f ./README.txt ]]; then
	echo "Updating the README.txt file"
	"${sed}" -r -e "s/Stable tag: ([0-9]+\.[0-9].*)/Stable\ tag:\ ${version}/g" \
	 				 -e "s/^Tested up to: ([0-9]+\.[0-9].*)/Tested up to: ${wordpress_version}/g"\
	 				 ./README.txt > ./NEW_README.txt
	mv ./NEW_README.txt ./README.txt
	cp ./README.txt ./README.md
	echo "Generating the README.md file"
	"${sed}" -r -e "s/^\= (.*) \=/## \1/g" \
					 -e "s/^\=\= (.*) \=\=/### \1/g" \
					 -e "s/^\=\=\= (.*) \=\=\=/### \1/g" \
					 -e "s/^\* (.*)$/- \1/g" \
					 -e "s/^([A-zA-Z ]*): ([A-zA-Z0-9\.\,\\\/: ]*)/\`\1\: \2\` <br \/>/g" \
					 ./README.md > NEW_README.md
	mv ./NEW_README.md ./README.md
fi



$(which git) commit -m "BUG FIX: Updated README info (v${version} for WP ${wordpress_version})" README.txt README.md

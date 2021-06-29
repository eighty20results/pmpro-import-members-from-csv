#!/usr/bin/env bash
short_name="pmpro-import-members-from-csv"
server="eighty20results.com"
sed="$(which sed)"
readme_path="./build_readmes/"
wordpress_version=$(wget -q -O - http://api.wordpress.org/core/stable-check/1.0/  | grep latest | awk '{ print $1 }' | sed -e 's/"//g')
# stripped_log=$(mktemp /tmp/old-info-XXXXXX)
version=$(grep -E "^Version:" ./class.pmpro-import-members.php | sed 's/[[:alpha:]|(|[:space:]|\:]//g' | awk -F- '{printf "%s", $1}')
today=$(date +%Y-%m-%d)

if [[ ! -f ./metadata.json ]]; then
	cp "${readme_path}/skel/metadata.json" ./metadata.json
fi

###########
#
# Update plugin and wordpress version info in metadata.json
#
if [[ -f ./metadata.json ]]; then
	echo "Updating the metadata.json file"
	"${sed}" -r -e "s/\"version\": \"([0-9]+\.[0-9].*)\"\,/\"version\": \"${version}\"\,/" \
					 -e "s/\"tested\"\:\ \"([0-9]+\.[0-9].*)\"\,/\"tested\"\:\ \"${wordpress_version}\"\,/" \
					 -e "s/\"last_updated\": \"(.*)\",/\"last_updated\": \"${today} $(date +%H:%M:00) CET\",/g" \
					 -e "s/\"download_url\": \"https:\/\/${server}\/protected-content\/${short_name}\/${short_name}-([0-9]+\.[0-9].*)\.zip\",/\"download_url\": \"https:\/\/${server}\/protected-content\/${short_name}\/${short_name}-${version}\.zip\",/g" \
					 ./metadata.json > ./new_metadata.json
		mv ./new_metadata.json ./metadata.json
fi

git commit -m "BUG FIX: Updated metdata.json for v${version} and WP ${wordpress_version}" ./metadata.json


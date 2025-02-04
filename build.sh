#!/usr/bin/env bash
CWD_BASENAME=${PWD##*/}

echo "Building front application"
cd front;
rm -rf node_modules;
npm install;
node build.js
cd ..;

echo "Building module";

rm -rf vendor;
composer install --no-dev;

FILES+=("${CWD_BASENAME}.php")
FILES+=("index.php")
FILES+=("LICENSE.txt")

DIRS+=("controllers");
DIRS+=("print");
DIRS+=("sql");
DIRS+=("src");
DIRS+=("vendor");
DIRS+=("views");

MODULE_VERSION="$(sed -ne "s/\\\$this->version *= *['\"]\([^'\"]*\)['\"] *;.*/\1/p" ${CWD_BASENAME}.php)"
MODULE_VERSION_FILE=`echo ${MODULE_VERSION} | sed -e "s/\./_/g"`;
MODULE_VERSION=${MODULE_VERSION//[[:space:]]}
ZIP_FILE="${CWD_BASENAME}-${MODULE_VERSION_FILE}.zip"


echo "Going to zip ${CWD_BASENAME} version ${MODULE_VERSION}"

cd ..
rm -f ${ZIP_FILE};

for E in "${FILES[@]}"; do
    find ${CWD_BASENAME}/${E}  -type f -exec zip -9 ${ZIP_FILE} {} \;
done

for D in "${DIRS[@]}"; do
    for E in `find ${CWD_BASENAME}/${D} -type f`; do
        case "$E" in
            *.csv | *.log | *.zip )
                echo "Skipping $E";
            ;;
            * )
                zip -9 ${ZIP_FILE} ${E};
            ;;
        esac;
    done;
done;

#!/bin/bash

release_dir=../websub.rocks-release

current=`pwd`

rsync -ap --delete app $release_dir/
rsync -ap --delete database $release_dir/
rsync -ap --delete lib $release_dir/
rsync -ap --delete public $release_dir/
rsync -ap --delete views $release_dir/
rsync -ap --delete --exclude=.git vendor $release_dir/
cp README.md $release_dir/
cp LICENSE $release_dir/

cd $release_dir
zip -r websubrocks.zip .

cd $current

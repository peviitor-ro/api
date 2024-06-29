# api
code for api.peviitor.ro

[![Deploy API to server](https://github.com/peviitor-ro/api/actions/workflows/deploy_api.yml/badge.svg)](https://github.com/peviitor-ro/api/actions/workflows/deploy_api.yml)


## run this command if not yet run previously
`cmd`
```
docker network create --subnet=172.18.0.0/16 mynetwork
```

## start API on local machine
1. create a folder c:\php
2. clone this repo in C:\php using GitHUB Desktop
3. `cmd`
```
docker run --name apache-container --network mynetwork --ip 172.18.0.11 -d -p 8080:80 -v C:/php:/var/www/html sebiboga/php-apache:1.0.0
```
4. in browser scrie [http://localhost:8080/api/v0/random/](http://localhost:8080/api/v0/random/)

## using _config
1. double click on run.bat from **_config** folder

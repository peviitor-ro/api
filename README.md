# api
code for api.peviitor.ro

[![Deploy API to server](https://github.com/peviitor-ro/api/actions/workflows/deploy_api.yml/badge.svg)](https://github.com/peviitor-ro/api/actions/workflows/deploy_api.yml)


## start on local machine
1. create a folder c:\php
2. clone this repo in C:\php using GitHUB Desktop
3. `cmd`
```
docker run -d -p 8080:80 -v C:/php:/var/www/html php-apache:1.0.0
```

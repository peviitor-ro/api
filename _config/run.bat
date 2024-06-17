@echo off
SETLOCAL

REM Check if Docker is running
docker info >nul 2>&1
IF ERRORLEVEL 1 (
   echo Docker is not running. Please start Docker Desktop and re-run the script.
   pause
   exit /b 1
)

SET NetworkName=mynetwork
SET ApacheContainerName=apache-container
SET SolrContainerName=solr-container
SET DataMigrationContainerName=data-migration
SET DataMigrationImageName=sebiboga/peviitor-data-migration-local:latest 

REM Remove existing containers if they exist
FOR %%C IN ("%ApacheContainerName%" "%SolrContainerName%" "%DataMigrationContainerName%") DO (
    docker ps -a --format "{{.Names}}" | findstr /C:%%~C >nul 2>&1 && (
        docker stop %%~C
        docker rm %%~C
    )
)

REM Check if the network exists; if not then create it
docker network ls --format "{{.Name}}" | findstr /C:"%NetworkName%" >nul 2>&1 || (docker network create --subnet=172.18.0.0/16 %NetworkName%)

REM Run the containers
docker run --name %ApacheContainerName% --network %NetworkName% --ip 172.18.0.11 -d -p 8080:80 -v C:/php:/var/www/html sebiboga/php-apache:1.0.0

TIMEOUT /T 10

docker run --name %SolrContainerName% --network %NetworkName% --ip 172.18.0.10 -d -p 8983:8983 -v c:\solrdata\solr\core:/var/solr sebiboga/peviitor:1.0.0

:loop
echo Waiting for solr container to be ready...
docker exec %SolrContainerName% nc -w 5 -z localhost 8983 >nul 2>&1
IF ERRORLEVEL 1 (
   echo Solr server not ready, waiting for 30 seconds before retry...
   TIMEOUT /T 30
   GOTO loop
)

docker run --name %DataMigrationContainerName% --network %NetworkName% --ip 172.18.0.12 --rm sebiboga/peviitor-data-migration-local:latest

REM Removing the data migration image
docker rmi %DataMigrationImageName%

ENDLOCAL
echo This script execution is completed.
pause